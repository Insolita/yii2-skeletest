<?php
/**
 * Created by solly [28.05.17 16:24]
 */

namespace tests\unit;

use Yii;
use Codeception\Specify;
use Codeception\Test\Unit;
use Codeception\Util\Debug;
use insolita\skeletest\entity\AppConfig;
use insolita\skeletest\entity\FileClass;
use insolita\skeletest\services\SkeletestService;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;

/**
 * Class SkeletestServiceTest
 *
 * @package tests\unit\extensions\skeletest
 */
class SkeletestServiceTest extends Unit
{
    use Specify;
    use \insolita\skeletest\AccessibleMethodTrait;
    
    /**
     * @test
     */
    public function createAppConfig()
    {
        $config = [
            'appPath'  => '@app',
            'testPath' => '@app/tests/codeception/unit',
            'testNs'   => 'tests\unit',
            'testerNs' => 'tests\UnitTester', //optional
        ];
        $service = new SkeletestService();
        $appConfig = $service->createAppConfig($config);
        verify($appConfig)->isInstanceOf(AppConfig::class);
        verify($appConfig->getAppPath())->equals(\Yii::getAlias('@app'));
        verify($appConfig->getTestPath())->equals(\Yii::getAlias('@app/tests/codeception/unit'));
        verify($appConfig->getTestNs())->equals('tests\unit');
        verify($appConfig->getTesterClass())->equals('tests\UnitTester');
    }
    
    /**
     * @test
     */
    public function getValidDirectoryPath()
    {
        \Yii::setAlias('@insolita/skeletest', FileHelper::normalizePath(Yii::getAlias('@tests/../src')));
        $service = new SkeletestService();
        $exists = '@insolita/skeletest/services';
        $valid = $service->getValidDirectoryPath($exists);
        verify($valid)->equals(\Yii::getAlias($exists));
        $this->specify(
            'invalidDirectoryPath',
            function () use ($service) {
                $service->getValidDirectoryPath('@app/fake/path');
            },
            ['throws' => InvalidParamException::class]
        );
    }
    
    /**
     * @test
     */
    public function getValidFilePath()
    {
        \Yii::setAlias('@insolita/skeletest', FileHelper::normalizePath(Yii::getAlias('@tests/../src')));
      
        $service = new SkeletestService();
        $exists = '@insolita/skeletest/services/SkeletestService';
        $valid = $service->getValidFilePath($exists);
        verify($valid)->equals(\Yii::getAlias($exists . '.php'));
        
        $this->specify(
            'invalidFilePath',
            function () use ($service) {
                $service->getValidFilePath('@app/fake/path');
            },
            ['throws' => InvalidParamException::class]
        );
    }
    
    /**
     * @test
     */
    public function createFileClass()
    {
        \Yii::setAlias('@insolita/skeletest', FileHelper::normalizePath(Yii::getAlias('@tests/../src')));
    
        $service = new SkeletestService();
        $fileClass = $service->createFileClass(\Yii::getAlias('@insolita/skeletest/services/SkeletestService.php'));
        verify($fileClass)->isInstanceOf(FileClass::class);
        verify($fileClass->getPath())->equals(\Yii::getAlias('@insolita/skeletest/services/SkeletestService.php'));
        verify($fileClass->getName())->equals('SkeletestService');
        verify($fileClass->getNamespace())->equals('insolita\skeletest\services');
        verify($fileClass->getFqnClass())->equals(SkeletestService::class);
        Debug::debug($fileClass);
    }
    
    /**
     * @test
     */
    public function guessTestFileClass()
    {
        $this->specify(
            'Tests under app root',
            function () {
                $service = new SkeletestService();
                $fileClass = $service->createFileClass(
                    \Yii::getAlias('@yii/base/Action.php')
                );
                $testFile = $service->guessTestFileClass(
                    $fileClass,
                    new AppConfig(
                        \Yii::getAlias('@yii'),
                        \Yii::getAlias('@yii/tests'),
                        'tests\unit'
                    )
                );
                verify($testFile)->isInstanceOf(FileClass::class);
                Debug::debug($testFile);
                
                verify($testFile->getPath())->equals(
                    \Yii::getAlias('@yii/tests/base/ActionTest.php')
                );
                verify($testFile->getName())->equals('ActionTest');
                verify($testFile->getNamespace())->equals('tests\unit\base');
                verify($testFile->getFqnClass())->equals('tests\unit\base\ActionTest');
            }
        );
        $this->specify(
            'Tests not under app root',
            function () {
                Debug::debug([
                    'basepath'=>\Yii::$app->basePath,
                    'basepath2'=>YII_APP_BASE_PATH,
                    'vendorpath'=>Yii::getAlias('@vendor'),
                    'yiipath'=>Yii::getAlias('@yii')
                             ]);
                $service = new SkeletestService();
                $fileClass = $service->createFileClass(
                    \Yii::getAlias('@yii/base/Action.php')
                );
                $testFile = $service->guessTestFileClass($fileClass,  new AppConfig(
                    \Yii::getAlias('@app'),
                    \Yii::getAlias('@tests/unit'),
                    'tests\unit',
                    'tests\UnitTester'
                ));
                Debug::debug($testFile);
                
                verify($testFile)->isInstanceOf(FileClass::class);
                verify($testFile->getPath())->equals(
                    \Yii::getAlias('@tests/unit/vendor/yiisoft/yii2/base/ActionTest.php')
                );
                verify($testFile->getName())->equals('ActionTest');
                verify($testFile->getNamespace())->equals('tests\unit\vendor\yiisoft\yii2\base');
                verify($testFile->getFqnClass())->equals('tests\unit\vendor\yiisoft\yii2\base\ActionTest');
            }
        );
    }
    
    /**
     * @test
     */
    public function createFileClassesFromDirectory()
    {
        $this->specify(
            'existed php classes',
            function () {
                $service = new SkeletestService();
                $files = $service->createFileClassesFromDirectory(
                    Yii::getAlias('@yii/di'),
                    new AppConfig(
                        \Yii::getAlias('@yii'),
                        \Yii::getAlias('@yii/tests'),
                        'tests\unit'
                    )
                );
                foreach ($files as $fileset) {
                    /**@var FileClass $file * */
                    /**@var FileClass $testFile * */
                    list($file, $testFile) = $fileset;
                    verify($file->getNamespace())->notEquals('');
                    verify($testFile)->isInstanceOf(FileClass::class);
                    verify(
                        in_array(
                            $testFile->getName(),
                            ['ContainerTest', 'InstanceTest', 'ServiceLocatorTest', 'NotInstantiableExceptionTest']
                        )
                    )->true();
                }
            }
        );
        $this->specify(
            'without php classes',
            function () {
                $service = new SkeletestService();
                $files = $service->createFileClassesFromDirectory(
                    Yii::getAlias('@yii/assets'),
                    new AppConfig(
                        \Yii::getAlias('@yii'),
                        \Yii::getAlias('@yii/tests'),
                        'tests\unit'
                    )
                );
                foreach ($files as $fileset) {
                    /**@var FileClass $file * */
                    list($file, $testFile) = $fileset;
                    verify($file->getNamespace())->equals('');
                    verify($testFile)->isEmpty();
                }
            }
        );
        
    }
    
    /**
     * @test
     */
    public function filterMethodsByPattern()
    {
        $service = new SkeletestService();
        $files = $service->createFileClassesFromDirectory(
            Yii::getAlias('@yii/di'),
            new AppConfig(
                \Yii::getAlias('@yii'),
                \Yii::getAlias('@yii/tests'),
                'tests\unit'
            )
        );
        foreach ($files as $fileset) {
            /**@var FileClass $file * */
            /**@var FileClass $testFile * */
            list($file, $testFile) = $fileset;
            verify($file->getNamespace())->notEquals('');
            verify($testFile)->isInstanceOf(FileClass::class);
            verify(
                in_array(
                    $testFile->getName(),
                    ['ContainerTest', 'InstanceTest', 'ServiceLocatorTest', 'NotInstantiableExceptionTest']
                )
            )->true();
        }
    }
    
    /**
     * @test
     */
    public function isPathMatched()
    {
        $service = new SkeletestService();
        $ignors = ['~(controllers|widget|interface|asset|event|contract|migration|exception)~i'];
        $filtered = [
            Yii::getAlias('@app/').'controllers/DefaultController.php',
            Yii::getAlias('@app/').'assets/AppAsset.php',
            Yii::getAlias('@app/').'common/dir/DummyInterface.php',
            Yii::getAlias('@app/').'common/dir/DummyException.php',
            Yii::getAlias('@app/').'migrations/qwertyuiop.php',
        ];

        verify($service->isPathMatched( Yii::getAlias('@app/').'models/User.php',$ignors))->false();
        foreach ($filtered as $path)
        {
            verify($service->isPathMatched($path,$ignors))->true();
        }
    }
    
    /**
     * @test
     */
    public function extractNamespace()
    {
        $c1 = <<<TXT
qwertyuiop
namespace app;
iuugiiuig
TXT;
        $c2 = <<<TXT
<?php
/**
 * comment
 */

namespace insolita\skeletest\services;

use app\extensions\FilePathResolver;
use Codeception\Test\Unit;
TXT;
        $c3 = <<<TXT
qwertyuiop
 namespace   app\q1\q2;
iuugiiuig
TXT;
        $c4 = <<<TXT
<?php
qwertyuiop
iuugiiuig
TXT;
        $fixture = [
            'empty'=>['content'=>'','expect'=>''],
            'c1'=>['content'=>$c1,'expect'=>'app'],
            'c2'=>['content'=>$c2,'expect'=>'insolita\skeletest\services'],
            'c3'=>['content'=>$c3,'expect'=>'app\q1\q2'],
            'c4'=>['content'=>$c4,'expect'=>''],
        ];
        $service = new SkeletestService();
        foreach ($fixture as $name=>$row)
        {
            $ns = $this->callMethod($service, 'extractNamespace', [$row['content']]);
            verify($ns)->equals($row['expect']);
        }
    }
    
    
    
    /**
     *
     */
    protected function _before()
    {
    }
}
