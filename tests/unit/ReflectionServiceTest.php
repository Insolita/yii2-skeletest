<?php
/**
 * Created by solly [28.05.17 16:25]
 */

namespace tests\unit;

use Codeception\Specify;
use Codeception\Test\Unit;
use Codeception\Util\Debug;
use insolita\skeletest\services\ReflectionService;
use yii\db\QueryTrait;
use yii\web\ErrorAction;
use yii\web\IdentityInterface;

/**
 * Class ReflectionServiceTest
 *
 * @package tests\unit\extensions\skeletest
 */
class ReflectionServiceTest extends Unit
{
    use Specify;
    use \insolita\skeletest\AccessibleMethodTrait;
    
    /**
     * @test
     */
    public function buildMethodFilter()
    {
        $service = new ReflectionService(true, true, true);
        $filter = $this->getProperty($service, 'reflectFilter');
        $service = new ReflectionService(false, false, false);
        $filter2 = $this->getProperty($service, 'reflectFilter');
        Debug::debug([$filter => $filter2]);
        verify($filter)->notEquals($filter2);
    }
    
    /**
     * @test
     */
    public function extractMethods()
    {
        $this->specify('normal class with all methods',function(){
            $service = new ReflectionService(true, true, true);
            $reflection = new \ReflectionClass(ErrorAction::class);
            $methods = $service->extractMethods($reflection);
            verify($methods)->hasKey('init');
            verify($methods)->hasKey('run');
            verify($methods)->hasKey('renderAjaxResponse');
            verify($methods)->hasKey('renderHtmlResponse');
            verify($methods)->hasKey('getViewRenderParams');
            verify($methods)->hasKey('findException');
        });
        $this->specify('normal class with public methods',function(){
            $service = new ReflectionService(false, false, false);
            $reflection = new \ReflectionClass(ErrorAction::class);
            $methods = $service->extractMethods($reflection);
            verify($methods)->hasKey('init');
            verify($methods)->hasKey('run');
            verify($methods)->hasntKey('renderAjaxResponse');
            verify($methods)->hasntKey('renderHtmlResponse');
            verify($methods)->hasntKey('getViewRenderParams');
        });
        $this->specify('interface',function(){
            $service = new ReflectionService(false, false, true);
            $reflection = new \ReflectionClass(IdentityInterface::class);
            $methods = $service->extractMethods($reflection);
            Debug::debug($methods);
            verify($methods)->hasKey('getId');
            verify($methods)->hasKey('getAuthKey');
            verify($methods)->hasKey('findIdentity');
            verify($methods)->hasKey('findIdentityByAccessToken');
            verify($methods['getId']['signature'])->equals('yii\web\IdentityInterface::getId()');
            verify($methods['getAuthKey']['signature'])->equals('yii\web\IdentityInterface::getAuthKey()');
            verify($methods['findIdentity']['signature'])->equals('yii\web\IdentityInterface::findIdentity($id)');
            verify($methods['findIdentity']['paramsig'])->equals('$id');
            verify($methods['findIdentityByAccessToken']['signature'])
                ->equals('yii\web\IdentityInterface::findIdentityByAccessToken($token,$type=null)');
            verify($methods['findIdentityByAccessToken']['paramsig'])->equals('$token,$type');
            verify($methods['findIdentityByAccessToken']['required'])->equals('$token');
        });
        $this->specify('trait ',function(){
            $service = new ReflectionService(false, false, false);
            $reflection = new \ReflectionClass(QueryTrait::class);
            $methods = $service->extractMethods($reflection);
            verify($methods)->hasKey('indexBy');
            verify($methods)->hasKey('andWhere');
        });
    }
    
    /**
     * @test
     */
    public function extractConstructor()
    {
        $this->specify('normal class ',function(){
            $service = new ReflectionService(false, false, false);
            $reflection = new \ReflectionClass(ErrorAction::class);
            $constructor = $service->extractConstructor($reflection);
            verify($constructor)->notNull();
            verify($constructor['paramsig'])->equals('$id,$controller,$config');
            verify($constructor['required'])->equals('$id,$controller');
            verify($constructor['signature'])
                ->equals('yii\base\Action::__construct($id,$controller,$config=null)');
            Debug::debug($constructor);
        });
        $this->specify('interface ',function(){
            $service = new ReflectionService(false, false, false);
            $reflection = new \ReflectionClass(IdentityInterface::class);
            $constructor = $service->extractConstructor($reflection);
            verify($constructor)->equals(null);
        });
        $this->specify('trait ',function(){
            $service = new ReflectionService(false, false, false);
            $reflection = new \ReflectionClass(QueryTrait::class);
            $constructor = $service->extractConstructor($reflection);
            verify($constructor)->equals(null);
        });
    }
}
