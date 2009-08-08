-- The capabilities system is simple enough. The whole thing is additive.
-- Users can have certain capabilities associated with them; they can also
-- be listed as members of zero or more groups and groups are collections of
-- capabilities. So the capabilities a user has are those explictly applied
-- to them combined with those of the groups they're members of.
-- 
-- Of course, this *does* lead to a proliferation of tables for managing the
-- relations between users, groups and capabilities.

CREATE TABLE capabilities (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        CHAR(24)          NOT NULL,
    description VARCHAR(255)      NOT NULL,

    PRIMARY KEY (id),
    UNIQUE INDEX ux_slug (slug)
) DEFAULT CHARSET=utf8;

CREATE TABLE groups (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        CHAR(24)          NOT NULL,
    description VARCHAR(255)      NOT NULL,

    PRIMARY KEY (id),
    UNIQUE INDEX ux_slug (slug)
) DEFAULT CHARSET=utf8;

CREATE TABLE users_groups (
    user_id  INTEGER UNSIGNED  NOT NULL,
    group_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (user_id, group_id)
);

CREATE TABLE groups_capabilities (
    group_id      SMALLINT UNSIGNED NOT NULL,
    capability_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (group_id, capability_id)
);

CREATE TABLE users_capabilities (
    user_id       INTEGER UNSIGNED  NOT NULL,
    capability_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (user_id, capability_id),
    INDEX ix_capability (capability_id)
);

-- The permissions view makes fetching the capabilities and groups a given user
-- Much easier than having to explicitly query the details.
CREATE VIEW permissions AS
SELECT  users_groups.user_id,
        capabilities.slug AS capability, capabilities.id AS capability_id,
        groups.slug AS `group`, groups.id AS group_id
FROM    users_groups
JOIN    groups_capabilities ON groups_capabilities.group_id      = users_groups.group_id
JOIN    capabilities        ON groups_capabilities.capability_id = capabilities.id
JOIN    groups              ON groups_capabilities.group_id      = groups.id
UNION
SELECT  users_capabilities.user_id,
        capabilities.slug, capabilities.id,
        NULL, NULL
FROM    users_capabilities
JOIN    capabilities        ON users_capabilities.capability_id = capabilities.id;
