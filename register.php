<?php

$REGISTER_LTI2 = array(
    "name" => "VULA file Admin UI",
    "FontAwesome" => "fa-server",
    "short_name" => "VULA Admin UI",
    "description" => "A simple tool that allows a admin user to manage files for the Vula homepage.",
    
    // By default, accept launch messages..
    "messages" => array("launch"),
    "privacy_level" => "name_only",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "tool_phase" => "new",
    "source_url" => "https://github.com/cilt-uct/tsugi-vula-home-page",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "file_navigation", "file_submission",
        "course_home_submission", "editor_button",
        "file_selection", "group_selection", "status_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
        "store/screen-01.PNG",
        "store/screen-02.PNG"
    )
);
