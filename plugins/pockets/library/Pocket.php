<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Addons\Pockets\PocketsModel;
use Vanilla\Widgets\WidgetService;

/**
 * Class Pocket
 */
class Pocket implements LoggerAwareInterface {

    use LoggerAwareTrait;

    const ENABLED = 0;
    const DISABLED = 1;
    const TESTING = 2;

    const REPEAT_BEFORE = 'before';
    const REPEAT_AFTER = 'after';
    const REPEAT_ONCE = 'once';
    const REPEAT_EVERY = 'every';
    const REPEAT_INDEX = 'index';

    const TYPE_AD = 'ad';
    const TYPE_DEFAULT = 'default';

    /** $var string The text to display in the pocket. */
    public $Body = '';

    /** @var int Whether or not the pocket is disabled. The pocket can also be in testing-mode. */
    public $Disabled = Pocket::ENABLED;

    /** @var string The format of the pocket. */
    public $Format = 'Raw';

    /** $var string The location on the page to display the pocket. */
    public $Location;

    /** $var string A descriptive name for the pocket to help keep it organized. */
    public $Name = '';

    /** $var string The name of the page to put the pocket on. */
    public $Page = '';

    /** $var string How the pocket repeats on the page. */
    public $RepeatType = Pocket::REPEAT_INDEX;

    /** $var array The repeat frequency. */
    public $RepeatFrequency = [1];

    /** $var array The repeat frequency. */
    public $MobileOnly = false;

    /** $var array The repeat frequency. */
    public $MobileNever = false;

    /** $var string Pocket type */
    public $Type;

    /** $var string Whether the pocket is in test mode. */
    public $TestMode = false;

    /** @var array */
    public $Data = [];

    /** $var bool Whether to disable the pocket for embedded comments. * */
    public $EmbeddedNever = false;

    /** @var bool  */
    public $ShowInDashboard = false;

    /** @var array */
    public static $NameTranslations = ['conversations' => 'inbox', 'messages' => 'inbox', 'discussion' => 'comments'];

    /** @var array */
    public $Attributes = [];

    /** @var string|null */
    private $widgetID = null;

    /** @var array */
    private $widgetParameters = [];

    /** @var WidgetService */
    private $widgetService;

    /**
     * Pocket constructor.
     */
    public function __construct(WidgetService $widgetService) {
        $this->widgetService = $widgetService;
        $this->setLogger(Logger::getLogger());
    }

    /**
     * The Disabled field used to have 3 states: enabled, disabled, or testing. Testing has since branched out
     * into its own field: TestMode. We need to check both places to see if a pocket is in test mode.
     *
     * @param Pocket|array $pocket
     *
     * @return bool
     */
    public static function inTestMode($pocket) {
        return (val('Disabled', $pocket) === Pocket::TESTING) || (val('TestMode', $pocket) === 1);
    }

    /**
     * Whether or not this pocket should be processed based on its state.
     *
     * @param array $data Data specific to the request.
     * @return bool
     */
    public function canRender($data) {
        $testMode = self::inTestMode($this);
        $pocketAdmin = checkPermission('Plugins.Pockets.Manage');
        $inSectionDashboard = inSection('Dashboard') || inSection('Moderation') || inSection('Settings') || inSection('Analytics') || inSection('DashboardHome');

        if (!$this->ShowInDashboard && $inSectionDashboard) {
            return false;
        }

        $isMobile = isMobile();
        if (($this->MobileOnly && !$isMobile) || ($this->MobileNever && $isMobile)) {
            return false;
        }

        if ($this->isAd() && checkPermission('Garden.NoAds.Allow')) {
            return false;
        }

        if ($this->EmbeddedNever && strcasecmp(Gdn::controller()->RequestMethod, 'embed') == 0) {
            return false;
        }

        if ($this->Disabled === Pocket::DISABLED) {
            return false;
        }

        if ($testMode && !$pocketAdmin) {
            return false;
        }

        // Check to see if the page matches.
        $page = $this->Page ?? '';
        $homepageMatches = (strtolower($page) === 'home' || strtolower($page) === 'sitehome') && $data['isHomepage'];
        $pageMatches = $homepageMatches || strtolower($page) === strtolower($data['PageName']);
        if ($page && !$pageMatches) {
            // A page is set, but we don't match it.
            return false;
        }

        // Check to see if this is repeating.
        $count = val('Count', $data);
        if ($count) {
            switch ($this->RepeatType) {
                case Pocket::REPEAT_AFTER:
                    if (strcasecmp($count, Pocket::REPEAT_AFTER) != 0) {
                        return false;
                    }
                    break;
                case Pocket::REPEAT_BEFORE:
                    if (strcasecmp($count, Pocket::REPEAT_BEFORE) != 0) {
                        return false;
                    }
                    break;
                case Pocket::REPEAT_ONCE:
                    if ($count != 1) {
                        return false;
                    }
                    break;
                case Pocket::REPEAT_EVERY:
                    $frequency = (array) $this->RepeatFrequency;
                    $every = val(0, $frequency, 1);
                    if ($every < 1) {
                        $every = 1;
                    }
                    $begin = val(1, $frequency, 1);
                    if (($count % $every) > 0 || ($count < $begin)) {
                        return false;
                    }
                    break;
                case Pocket::REPEAT_INDEX:
                    if (!in_array($count, (array)$this->RepeatFrequency)) {
                        return false;
                    }
                    break;
            }
        }

        /** @var \Garden\EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(\Garden\EventManager::class);
        $eventResult = $eventManager->fireFilter('pocket_canRender', true, $this, $data);

        return $eventResult;
    }

    /**
     * Load the pocket's data from an array.
     *
     * @param array $data
     */
    public function load($data) {
        $this->Body = $data['Body'];
        $this->Disabled = $data['Disabled'];
        $this->Format = $data['Format'];
        $this->Location = $data['Location'];
        $this->Name = $data['Name'];
        $this->Page = $data['Page'];
        $this->MobileOnly = $data['MobileOnly'];
        $this->MobileNever = $data['MobileNever'];
        $this->Type = $data['Type'] ?? Pocket::TYPE_DEFAULT;
        $this->EmbeddedNever = $data['EmbeddedNever'] ?? null;
        $this->ShowInDashboard = $data['ShowInDashboard'] ?? $data;
        $this->TestMode = $data['TestMode'] ?? null;
        $this->Data = $data;
        $this->widgetParameters = $data['WidgetParameters'] ?? [];
        $this->widgetID = $data['WidgetID'] ?? null;

        // parse the frequency.
        $repeat = $data['Repeat'];
        [$this->RepeatType, $this->RepeatFrequency] = Pocket::parseRepeat($repeat);
    }

    /**
     * Determine whether the pocket is of type 'ad'.
     *
     * @return bool
     */
    public function isAd() {
        return $this->Type == Pocket::TYPE_AD;
    }

    /**
     * Attempt to determine the name of the page from the passed object.
     *
     * - Checks `PageName`.
     * - Checks `ControllerName`.
     * - Falls back to the class name.
     *
     * Then transforms and translates the name with {self::$NameTranslations}
     *
     * @param string|object|null $nameOrObject The string or object to pull from.
     *
     * @return string A page name or empty string if null/no argument was passed.
     */
    public static function pageName($nameOrObject = null): string {
        if (is_object($nameOrObject)) {
            $name = val('PageName', $nameOrObject, val('ControllerName', $nameOrObject, get_class($nameOrObject)));
        } else {
            $name = $nameOrObject;
        }

        $name = strtolower($name);
        if (stringEndsWith($name, 'controller', false)) {
            $name = substr($name, 0, -strlen('controller'));
        }

        if (array_key_exists($name, self::$NameTranslations)) {
            $name = self::$NameTranslations[$name];
        }
        return $name;
    }

    /**
     * Normalize the repeat value of a pocket.
     *
     * @param string $repeat The repeat value.
     *
     * @return array A tuple of the repeat value and frequency in the form `[string, string[]]`.
     */
    public static function parseRepeat($repeat) {
        $repeatType = '';
        if (stringBeginsWith($repeat, Pocket::REPEAT_EVERY)) {
            $repeatType = Pocket::REPEAT_EVERY;
            $frequency = substr($repeat, strlen(Pocket::REPEAT_EVERY));
        } elseif (stringBeginsWith($repeat, Pocket::REPEAT_INDEX)) {
            $repeatType = Pocket::REPEAT_INDEX;
            $frequency = substr($repeat, strlen(Pocket::REPEAT_INDEX));
        } elseif (stringBeginsWith($repeat, Pocket::REPEAT_ONCE)) {
            $repeatType = Pocket::REPEAT_ONCE;
        } elseif (stringBeginsWith($repeat, Pocket::REPEAT_BEFORE)) {
            $repeatType = Pocket::REPEAT_BEFORE;
        } elseif (stringBeginsWith($repeat, Pocket::REPEAT_AFTER)) {
            $repeatType = Pocket::REPEAT_AFTER;
        }

        if (isset($frequency)) {
            $frequency = explode(',', $frequency);
            $frequency = array_map('trim', $frequency);
        } else {
            $frequency = [];
        }

        return [$repeatType, $frequency];
    }

    /**
     * Render the pocket to the page.
     *
     *  @param array $data additional data for the pocket.
     */
    public function render($data = null) {
        echo $this->toString($data);
    }

    /**
     * Set the repeat of the pocket.
     *
     * @param string $type The repeat type, contained in the various Pocket::REPEAT_* constants.
     * - every: Repeats every x times. If $frequency is an array then it will be interpretted as array($frequency, $Begin).
     * - indexes: Renders only at the given indexes, starting at 1.
     * @param int|array $frequency The frequency of the repeating, see the $type parameter for how this works.
     */
    public function repeat($type, $frequency) {
        $this->RepeatType = $type;
        $this->RepeatFrequency = $frequency;
    }

    /**
     * Get a string representation of the Pocket.
     *
     * @return mixed|string Either the raw body of the pocket or a formatted version, depending on the Format.
     */
    public function toString() {
        static $plugin;
        if (!isset($plugin)) {
            $plugin = PocketsPlugin::instance();
        }

        $plugin->EventArguments['Pocket'] = $this;
        $plugin->fireEvent('ToString');

        $format = strtolower($this->Format);

        switch ($format) {
            case PocketsModel::FORMAT_CUSTOM:
                return $this->Body;
            case PocketsModel::FORMAT_WIDGET:
                $factory = $this->widgetService->getFactoryByID($this->widgetID);
                if (!$factory) {
                    $this->logger->warning("Could not find widget factory for pocket", [
                        'pocketName' => $this->Name,
                        'widgetID' => $this->widgetID,
                    ]);
                    return '';
                }
                $output = $factory->renderWidget($this->widgetParameters);
                return $output;
            default:
                return \Gdn::formatService()->renderHTML($this->Body, $this->Format);
        }
    }

    /**
     * Create a pocket if it doesn't already exist.
     *
     * @param string $name The name of the pocket.
     * @param string $value The contents of the pocket.
     *
     * @return int|false
     *
     * @deprecated PocketsModel::touchPocket()
     */
    public static function touch($name, $value) {
        /** @var PocketsModel $model */
        $model = \Gdn::getContainer()->get(PocketsModel::class);
        return $model->touchPocket($name, [
            'Body' => $value,
        ]);
    }
}
