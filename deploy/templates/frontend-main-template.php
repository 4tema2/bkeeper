<?php
/**
 * main.php
 *
 * @author: artuom proskunin <artuomv.proskunin@gmail.com>
 */

/**
 * Настройки РНР
 * Могут быть переопределенны в в main-ENVIRONMENT.php см. конец файла
 */
date_default_timezone_set('Europe/Moscow');
error_reporting("%%php_error_reporting%%");
ini_set('display_errors', "%%php_display_errors%%");

/**
 * Базовые дирректории
 */
$frontendConfigDir = dirname(__FILE__);
$root = $frontendConfigDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';

/**
 * Специфичные для данного приложения конфигурационные файлы и настройки
 * Часть содержимого $config
 */

/**
 * Например:
 * Файл additionalSettings содержит часть основного конфига в виде:
 * return array(
 *      ...
 *      'some_param' => 'some_value'
 *      ...
 * );
 * и добавляется в него путем присваивания переменной $additionalSettingsConfiguration
 * в основном конфиге
 * $config = array(
 *      ...
 *      'additional_settings' => $additionalSettingsConfiguration
 *      ...
 * );
 *
 * $additionalSettingsConfiguration получается следующим образом:
 * $additionalSettingsFile = $root . DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'additionalSettings.php';
 * $additionalSettingsConfiguration = file_exists($additionalSettingsFile) ? require($additionalSettingsFile) : array();
 *
 * Так же тут устанавливаем специфичные для всех окружений, но для этого приложения
 * переменные PHP:
 *
 * ini_set('SOME_PARAM', 'SOME_VALUE');
 */

/**
 * Устанавливаем некоторые алиасы путей, для удобства их использования и для
 * системных нужд.
 *
 * Все прочие системные алиасы будут относительно установленного 'root'
 */
Yii::setPathOfAlias('root', $root);
Yii::setPathOfAlias('common', $root . DIRECTORY_SEPARATOR . 'common');
Yii::setPathOfAlias('frontend', $root . DIRECTORY_SEPARATOR . 'frontend');
Yii::setPathOfAlias('www', $root. DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'www');
Yii::setPathOfAlias('bootstrap', $root. DIRECTORY_SEPARATOR . 'common' . DIRECTORY_SEPARATOR . 'extensions'. DIRECTORY_SEPARATOR . 'bootstrap');

/**
 * Основной конфигурационный файл приложения
 */
$config = array(

    // @see http://www.yiiframework.com/doc/api/1.1/CApplication#basePath-detail
    'basePath' => 'frontend',

    'name' => 'WebApplication',

    'sourceLanguage' => 'en',

    // @see http://www.yiiframework.com/doc/api/1.1/CApplication#language-detail
    'language' => 'en',

    //'theme'=> 'bootstrap',

    // preload components required before running applications
    // @see http://www.yiiframework.com/doc/api/1.1/CModule#preload-detail
    'preload'=>array('log', 'bootstrap'),

    // autoloading model and component classes
    // @see http://www.yiiframework.com/doc/api/1.1/YiiBase#import-detail
    'import'=>array(
        'frontend.components.*',
        'frontend.models.*',
        'frontend.extensions.*',
        'common.helpers.*',
        'common.extensions.*',
        'common.models.*',
    ),

    // application components
    'components' => array(
        'user' => array(

        ),

       'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => false,
            'urlSuffix' => '/',
            'rules'=>array(
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ),
        ),

        'bootstrap' => array(
            'class' => 'bootstrap.components.Bootstrap',
        ),

        'db' => array(
            'class' => 'CDbConnection',
            'connectionString' => 'mysql:host=%%mysql_host%%;port=%%mysql_port%%;dbname=%%mysql_dbname%%',
            'emulatePrepare' => true,
            'username' => '%%mysql_username%%',
            'password' => '%%mysql_password%%',
            'charset' => 'utf8',
        ),

        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'info, error, warning',
                    'maxFileSize' => '10240',
                    'maxLogFiles' => '25',
                    'logFile' => 'application.log',
                ),
            )
        ),

        /*
        'errorHandler' => array(
            // @see http://www.yiiframework.com/doc/api/1.1/CErrorHandler#errorAction-detail
            'errorAction'=>'site/error'
        ),
        */
    ),

    // application-level parameters that can be accessed
    // using Yii::app()->params['paramName']
    'params' => array(
        'adminEmail' => '%%admin_email%%',
    ),

);

return $config;
