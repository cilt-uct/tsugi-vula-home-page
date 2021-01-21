<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}vula_files"
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}vula_files",
"create table {$CFG->dbprefix}vula_files (
    `category` varchar(11) NOT NULL,
    `file_name` varchar(11) NOT NULL,
    `expires` datetime DEFAULT CURRENT_TIMESTAMP,
    `url` varchar(255) DEFAULT NULL,
    `file_size` int(22) NOT NULL,
    `file_dimensions` varchar(64) NOT NULL,
    `submitter` varchar(64) DEFAULT NULL,
    `jira_issue` varchar(64) DEFAULT NULL,
    `created` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`file_name`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);


// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
    "drop table if exists {$CFG->dbprefix}vula_authorization"
    );
    
    // The SQL to create the tables if they don't exist
    $DATABASE_INSTALL = array(
    array( "{$CFG->dbprefix}vula_authorization",
    "create table {$CFG->dbprefix}vula_authorization (
        `id` int(11) NOT NULL,
        `email_address` varchar(255) NOT NULL,
        `name` varchar(255) NOT NULL,
        `role` varchar(255) NOT NULL,
        `username` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB DEFAULT CHARSET=utf8")
    );


