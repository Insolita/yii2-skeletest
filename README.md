Test Skeleton generator
=======================
Generate test skeletons for file or whole directory with target file methods (codeception/phpunit - based on template) 

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist insolita/yii2-skeletest "~1.0"
```

or add

```
"insolita/yii2-skeletest": "~1.0"
```

to the require section of your `composer.json` file.


Usage
-----

in console application configure add to controllerMap section

```php
'skeletest'=>[
            'class'=>\insolita\skeletest\controllers\SkeletestController::class,
            'pathMap' => [
            //register each application with own test directory - for basic template like this
                            'app'=>[
                                'appPath'  => '@app/',
                                'testPath' => '@app/tests/codeception/unit',
                                'testNs'   => 'tests\codeception\unit',
                                'testerNs' => 'tests\codeception\UnitTester',
                            ]
                        ],
            'app'=>'app',//default application key
            'templateFile' => '@vendor/insolita/yii2-skeletest/templates/codeception.php', //or own template
            'overwrite' => false, //overwrite existed test
            'ignoreGetters' => true, //skip getter methods
            'ignoreSetters' => true, //skip setter methods
            'withProtectedMethods' => false, //include protected methods in test skeleton
            'withPrivateMethods' => false,//include private methods in test skeleton
            'withStaticMethods' => true,//include static methods in test skeleton
            'ignoreFilePatterns'=>['~(controllers|widget|asset|interface|contract|migration)~i'],//array of regexp patterns for skip files
            'ignoreMethodPatterns'=>['~^(behaviors|find|rules|tableName|attributeLabels|scenarios)$~'],//array of regexp patterns for skip methods
        ],
```

after configuration you can use it in console

- generate single test by file alias
```
  ./yii skeletest @app/components/MyComponent.php
```
- generate tests recursive for all files by directory alias (Be careful, if directory contains sub-directory with non-psr namespace roots, for that directories you must generate test directly)
  ```
  ./yii skeletest/dir @frontend/services/registration
  ```
- show options
  ```
  ./yii help skeletest/file
  ```
 Template customization
 ----------------------
 Copy @vendor/insolita/yii2-skeletest/templates/codeception.php in any project directory and modify as you want
 Change in controllerMap 'templateFile' parameter of skeletest controller  to you own template path

