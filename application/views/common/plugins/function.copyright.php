<?php
/*
 * Smarty Copyright Plugin
 * Usage: {copyright} or {copyright holder="Custom Name" start="2010"}
 */

function smarty_function_copyright($params, $smarty) {
    if (!class_exists('CopyrightHelper')) {
        require_once dirname(__FILE__) . '/../../../controllers/helpers/copyright_helper.php';
    }
    
    $holder = isset($params['holder']) ? $params['holder'] : 'Stephen Lawrence Jr.';
    $startYear = isset($params['start']) ? (int)$params['start'] : 2000;
    $format = isset($params['format']) ? $params['format'] : 'html';
    
    return ($format === 'text') 
        ? CopyrightHelper::getSourceNotice($holder, $startYear)
        : CopyrightHelper::getNotice($holder, $startYear);
}
