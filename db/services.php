<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'block_igis_ollama_claude_get_chat_response' => array(
        'classname'     => 'block_igis_ollama_claude_external',
        'methodname'    => 'get_chat_response',
        'description'   => 'Get a response from the selected AI provider',
        'type'          => 'read',
        'capabilities'  => '',
        'ajax'          => true,
        'loginrequired' => false,
        'component'     => 'block_igis_ollama_claude',
    ),
    'block_igis_ollama_claude_clear_conversation' => array(
        'classname'     => 'block_igis_ollama_claude_external',
        'methodname'    => 'clear_conversation',
        'description'   => 'Clear the conversation history',
        'type'          => 'write',
        'capabilities'  => '',
        'ajax'          => true,
        'loginrequired' => false,
        'component'     => 'block_igis_ollama_claude',
    ),
);

$services = array(
    'Multi-provider AI Chat Services' => array(
        'functions' => array(
            'block_igis_ollama_claude_get_chat_response',
            'block_igis_ollama_claude_clear_conversation',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'igis_ollama_claude_service',
        'downloadfiles' => 0,
        'uploadfiles' => 0
    ),
);