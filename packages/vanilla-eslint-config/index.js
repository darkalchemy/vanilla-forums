/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

module.exports = {
    parser: "@typescript-eslint/parser",
    plugins: ["@typescript-eslint", "@vanilla", "react", "react-hooks", "jsx-a11y", "lodash"],
    extends: [
        "eslint:recommended",
        "plugin:react/recommended",
        "plugin:@typescript-eslint/recommended",
        // "plugin:jsx-a11y/recommended",
        "prettier",
        "prettier/react",
        "prettier/@typescript-eslint",
    ],
    parserOptions: {
        project: "./tsconfig.json",
    },
    settings: {
        react: {
            version: "detect",
        },
    },
    rules: {
        // Just distasteful
        "no-prototype-builtins": "off",
        "@typescript-eslint/no-inferrable-types": "off",
        "@typescript-eslint/ban-ts-ignore": "off",
        "no-self-assign": "off",
        "no-case-declarations": "off",
        "prefer-const": "off",
        "require-atomic-updates": "off",
        "react/prop-types": "off", // We use typescript

        // We have typescript for these.
        "no-undef": "off",
        "getter-return": "off",
        "no-dupe-class-members": "off",

        // General JS restrictions
        "no-console": ["error", { allow: ["warn", "error"] }],
        "no-debugger": ["error"],
        "no-alert": ["error"],

        // Typescript specific rules
        "@typescript-eslint/ban-types": [
            "error",
            {
                types: {
                    Object: "Avoid using the `Object` type. Did you mean `object`?",
                    Function: "Avoid using the `Function` type. Prefer a specific function type, like `() => void`.",
                    Boolean: "Avoid using the `Boolean` type. Did you mean `boolean`?",
                    Number: "Avoid using the `Number` type. Did you mean `number`?",
                    String: "Avoid using the `String` type. Did you mean `string`?",
                    Symbol: "Avoid using the `Symbol` type. Did you mean `symbol`?",
                    object: false,
                    "{}": false
                },
            },
        ],
        "@typescript-eslint/array-type": ["error", { default: 'array-simple' }],
        "@typescript-eslint/ban-ts-comment": "off",
        "@typescript-eslint/camelcase": "off",
        "@typescript-eslint/explicit-module-boundary-types": "off",
        "@typescript-eslint/no-empty-function": "off",
        "@typescript-eslint/no-empty-interface": "off",
        "@typescript-eslint/no-parameter-properties": "off",
        "@typescript-eslint/no-use-before-define": "off",
        "@typescript-eslint/no-object-literal-type-assertion": "off",
        "@typescript-eslint/no-var-requires": "off",

        // These are currently too strict to enable here
        "@typescript-eslint/no-explicit-any": "off",
        "@typescript-eslint/no-non-null-assertion": "off",
        "@typescript-eslint/explicit-function-return-type": "off",

        // A11Y
        "jsx-a11y/html-has-lang": "off", // https://github.com/evcohen/eslint-plugin-jsx-a11y/issues/565
        "react/jsx-no-target-blank": "off", // Our <SmartLink /> handles this. Detection is janky.

        // I want to be able to turn these on the future.
        // but they each have a lot of files to fix and should be their own PR.
        "@typescript-eslint/explicit-member-accessibility": "off",
        "@typescript-eslint/no-unused-vars": "off",
        "@typescript-eslint/await-thenable": "off",
        "@typescript-eslint/promise-function-async": "off",

        // React hooks
        "react-hooks/rules-of-hooks": "error",
        "react-hooks/exhaustive-deps": "warn",

        // Vanilla Custom
        "@vanilla/no-unconventional-imports": "error",
        "@vanilla/no-cloud-imports-in-core": "error",

        // Lodash
        // Ensure we always do single package lodash imports.
        // Eg. import debounce from "lodash/debounce"
        "lodash/import-scope": ["error", "method"],
    },
};
