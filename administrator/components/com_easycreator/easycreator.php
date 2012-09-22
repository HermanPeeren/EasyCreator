<?php defined('_JEXEC') || die('=;)');
/**
 * @package    EasyCreator
 * @subpackage Base
 * @author     Nikolai Plath (elkuku)
 * @author     Created on 06-Mar-2008
 * @license    GNU/GPL, see JROOT/LICENSE.php
 */

//-- When changing Joomla! versions look for:
//-- @Joomla!-version-check
//-- @Joomla!-compat XXXX

//-- Dev mode - internal use =;)
//-- @@DEBUG
define('ECR_DEV_MODE', 1);

JDEBUG ? JProfiler::getInstance('Application')->mark('com_easycreator starting') : null;

//@todo legacy imports...
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

//-- Global constants
require JPATH_COMPONENT.'/includes/defines.php';

//-- Global functions
require JPATH_COMPONENT.'/includes/loader.php';

if(ECR_DEV_MODE)
{
    //-- Setup debugger
    if(JComponentHelper::getParams('com_easycreator')->get('ecr_debug'))
    {
        //-- Set debugging ON
        define('ECR_DEBUG', true);
    }
    else
    {
        define('ECR_DEBUG', false);
    }

    if(JComponentHelper::getParams('com_easycreator')->get('ecr_debug_lang', 0))
    {
        //-- Set debugging ON
        define('ECR_DEBUG_LANG', 1);
    }
    else
    {
        define('ECR_DEBUG_LANG', 0);
    }
}
else
{
    define('ECR_DEBUG', 0);
}

//-- Load the special Language

//-- 1) Check if g11n is installed as a PEAR package - see: http://elkuku.github.com/pear/

//@include 'elkuku/g11n/language.php';

//-- @todo: check for installed g11n PEAR package to remove the "shut-up"
//-- @require_once 'elkuku/g11n/language.php';

try
{
    if( ! class_exists('g11n'))
    {
        //-- 2) Check the libraries folder

        if( ! jimport('g11n.language'))
        {
            //-- 3) Load a dummy language handler -> english only !

            ecrLoadHelper('g11n_dummy');

            ecrScript('g11n_dummy', 'php2js');
        }
    }

    if(class_exists('g11n'))
    {
        //-- TEMP@@debug
        if(ECR_DEV_MODE && ECR_DEBUG_LANG)
        {
            //-- @@DEBUG
            g11n::cleanStorage();
            g11n::setDebug(ECR_DEBUG_LANG);
        }

        //-- Get our special language file
        g11n::loadLanguage();
    }
}
catch(Exception $e)
{
    JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

    return;
}

/**
 * EasyCreator Version
 */
define('ECR_VERSION', EcrProjectHelper::parseXMLInstallFile(
    JPATH_COMPONENT_ADMINISTRATOR.DS.'easycreator.xml')->version);

/**
 * Check the Joomla! version
 *
 * @Joomla!-version-check
 */
switch(ECR_JVERSION)
{
    case '3.0': //-- Get prepared
        $application = JFactory::getApplication();

        $application->JComponentTitle = 'EasyCreator';

        /*
        $application->enqueueMessage(sprintf(
            jgettext(
                'EasyCreator version %s is in testing stage with your Joomla! version %s'
            )
            , ECR_VERSION, ECR_JVERSION), 'warning'
        );
        */
        break;

    case '2.5':
        //-- We're all OK
        break;

    default:
        JFactory::getApplication()->enqueueMessage(sprintf(
            jgettext('EasyCreator version %s may not work well with your Joomla! version %s')
            , ECR_VERSION, ECR_JVERSION), 'error');
        break;
}

//-- Add CSS
ecrStylesheet('bootstrap', 'default', 'toolbar', 'icon');

//-- @todo remove bootstrap when it is integrated in J! 3.0
#ecrStylesheet('default', 'toolbar', 'icon');

//-- Setup tooltips - used almost everywhere..
JHTML::_('behavior.tooltip');

//-- Add JavaScript
ecrScript('global_vars', 'easycreator');

JFactory::getDocument()->addScriptDeclaration("var ECR_JVERSION = '".ECR_JVERSION."';".NL);
JFactory::getDocument()->addScriptDeclaration("var ECR_VERSION = '".ECR_VERSION."';".NL);

$prevErrorReporting = error_reporting(- 1);

try
{
    $controller = EcrEasycreator::getController();

    if('component' == JRequest::getCmd('tmpl'))
    {
        //-- Perform the Request task only - raw view
        $controller->execute(JRequest::getCmd('task'));
    }
    else
    {
        //-- Display the menu
        EcrHtmlMenu::main();

        //-- Perform the Request task
        $controller->execute(JRequest::getCmd('task'));

        if(ECR_DEV_MODE && ECR_DEBUG_LANG
            && class_exists('g11n')
        )
        {
            g11n::debugPrintTranslateds(true);
            g11n::debugPrintTranslateds();
        }

        //-- Display the footer
        EcrHtml::footer();

        JDEBUG ? JProfiler::getInstance('Application')->mark('com_easycreator finished') : null;
    }

    //-- Restore error_reporting
    error_reporting($prevErrorReporting);

//-- Redirect if set by the controller
//-- We don't do this very often =;)
    $controller->redirect();
}
catch(Exception $e)
{
    EcrHtml::message($e);
    JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');

    if(ECR_DEBUG)
        echo '<pre>'.$e->getTraceAsString().'</pre>';
}

//-- Restore error_reporting
error_reporting($prevErrorReporting);
