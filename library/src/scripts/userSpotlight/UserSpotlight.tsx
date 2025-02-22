/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import { DeepPartial } from "redux";
import { userSpotlightVariables, IUserSpotlightOptions } from "./UserSpotlight.variables";
import { userSpotlightClasses } from "./UserSpotlight.classes";
import ProfileLink from "@library/navigation/ProfileLink";
import { IUserFragment } from "@library/@types/api/users";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { useMeasure } from "@vanilla/react-utils/src";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";

export interface IUserSpotlightProps {
    title?: string;
    description?: string;
    options: DeepPartial<IUserSpotlightOptions>;
    userInfo: IUserFragment;
}

export function UserSpotlight(props: IUserSpotlightProps) {
    const { title, description, options, userInfo } = props;
    const rootRef = useRef<HTMLDivElement | null>(null);
    const rootMeasure = useMeasure(rootRef);
    const shouldWrap = rootMeasure.width > 0 && rootMeasure.width < forumLayoutVariables().panel.paddedWidth;
    const vars = userSpotlightVariables(options);
    const classes = userSpotlightClasses(shouldWrap, options);

    return (
        <div ref={rootRef} className={classes.root}>
            <div className={classes.avatarContainer}>
                <ProfileLink userFragment={userInfo} className={classes.avatarLink}>
                    <UserPhoto userInfo={userInfo} size={vars.avatar.size} className={classes.avatar} />
                </ProfileLink>
            </div>
            <div className={classes.textContainer}>
                <div className={classes.title}>{title}</div>
                <div className={classes.description}>{description}</div>
                <div className={classes.userText}>
                    <ProfileLink userFragment={userInfo} className={classes.userName}>
                        {userInfo.name}
                        {userInfo.title && <span>, </span>}
                    </ProfileLink>
                    {userInfo.title && <span className={classes.userTitle}>{userInfo.title}</span>}
                </div>
            </div>
        </div>
    );
}
