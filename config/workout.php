<?php

return [

    /*
    | The shared password for the phone screen. Leave empty to skip the screen.
    */

    'password' => env('WORKOUT_PASSWORD', 'gamal123'),

    /*
    | Bearer / X-Workout-Token for the read-only MCP API. Leave empty to disable it.
    */

    'mcp_token' => env('WORKOUT_MCP_TOKEN', ''),

];
