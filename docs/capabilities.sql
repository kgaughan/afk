-- 
-- The capabilities system is simple enough. The whole thing is additive.
-- Users can have certain capabilities associated with them; they can also
-- be listed as members of zero or more groups and groups are collections of
-- capabilities. So the capabilities a user has are those explictly applied
-- to them combined with those of the groups they're members of.
-- 
-- Of course, this *does* lead to a proliferation of tables for managing the
-- relations between users, groups and capabilities.
-- 
-- Slug are short human-readable tokens use to identify a capability or
-- group and are used in code as such. On the other hand, the IDs assigned
-- to capabilities and groups are purely there for internally relating
-- groups, users and capabilities to one another and carry no semantics.
-- 
-- Ideally, your program's code should not care about groups at all and
-- should focus on what capabilities are assigned to the user either
-- indirectly through group membership or by direct assignment of
-- capabilities. By doing so, the code has no dependencies on how the
-- administrator or manager decides to assign access to the application,
-- thus avoiding the brittleness of hardwired groups.
-- 
-- To reiterate:
-- 
--     DON'T TEST FOR GROUP MEMBERSHIP; TEST FOR SETS OF CAPABILITIES.
--     RELY ON GROUPS HAVING THE NECESSARY CAPABILITIES ASSIGNED TO THEM.
-- 
-- It's worth noting, however, that groups can have no capabilities assigned
-- to them. This makes capabilitiless groups useful for coarse-grained
-- ownership scenarios.
-- 

-- Capabilities are things that a user/group is allowed to do.
CREATE TABLE capabilities (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        CHAR(24)          NOT NULL,
    description VARCHAR(255)      NOT NULL,

    PRIMARY KEY (id),
    UNIQUE INDEX ux_slug (slug)
) DEFAULT CHARSET=utf8;

-- Groups are sets of users. They're also sets of capabilities, and
-- membership of a group gives all of the capabilities of that group to its
-- member users.
CREATE TABLE groups (
    id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        CHAR(24)          NOT NULL,
    description VARCHAR(255)      NOT NULL,

    PRIMARY KEY (id),
    UNIQUE INDEX ux_slug (slug)
) DEFAULT CHARSET=utf8;

-- Relating users to groups.
CREATE TABLE users_groups (
    user_id  INTEGER UNSIGNED  NOT NULL,
    group_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (user_id, group_id)
);

-- Relating capabilities to groups.
CREATE TABLE groups_capabilities (
    group_id      SMALLINT UNSIGNED NOT NULL,
    capability_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (group_id, capability_id)
);

-- Relating users directly to capabilities. Sometimes users need special
-- capabilities not assigned to any group they're a member of, and it's
-- in this table those capabilities can be directly assigned to them.
CREATE TABLE users_capabilities (
    user_id       INTEGER UNSIGNED  NOT NULL,
    capability_id SMALLINT UNSIGNED NOT NULL,

    PRIMARY KEY (user_id, capability_id),
    INDEX ix_capability (capability_id)
);

--
-- The permissions view makes fetching the capabilities and groups of a given
-- user. Much easier than having to explicitly query the details.
--
-- When pulling out permissions initially, you'll want to use this table like
-- so:
-- 
--   SELECT    id, uname, name, email,
--             GROUP_CONCAT(DISTINCT capability SEPARATOR ',') AS capabilities,
--             GROUP_CONCAT(DISTINCT `group` SEPARATOR ',') AS groups
--   FROM      users
--   LEFT JOIN permissions ON id = user_id
--   WHERE     id = ?;
-- 
-- Or something similar.
-- 
CREATE VIEW permissions AS
SELECT    users_groups.user_id,
          capabilities.slug AS capability, capabilities.id AS capability_id,
          groups.slug AS `group`, groups.id AS group_id
FROM      users_groups
JOIN      groups              ON users_groups.group_id             = groups.id
LEFT JOIN groups_capabilities ON groups_capabilities.group_id      = users_groups.group_id
JOIN      capabilities        ON groups_capabilities.capability_id = capabilities.id
UNION
SELECT    users_capabilities.user_id,
          capabilities.slug, capabilities.id,
          NULL, NULL
FROM      users_capabilities
JOIN      capabilities        ON users_capabilities.capability_id = capabilities.id;
