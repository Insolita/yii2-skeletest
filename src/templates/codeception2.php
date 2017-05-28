<?php
/**
 * @var array $methods
 * @var \insolita\skeletest\entity\FileClass $testFile
 * @var \insolita\skeletest\entity\AppConfig $app
 * @var \ReflectionClass $reflection
 * @var bool $useAccessibleTrait
 **/
echo "<?php".PHP_EOL;
echo "namespace {$testFile->getNamespace()};".PHP_EOL.PHP_EOL;
?>
use Codeception\Test\Unit;
use Codeception\Specify;
use Codeception\Util\Stub;
use <?=$reflection->getName();?>;
<?php if($useAccessibleTrait===true):?>
use insolita\skeletest\AccessibleMethodTrait;
<?php endif;?>
<?php if($app->getTesterClass()):?>
	use <?=$app->getTesterClass()?>;
<?php endif;?>
/**
*  Class <?=$testFile->getName().PHP_EOL ?>
*  Test for <?=$reflection->getName().PHP_EOL?>
**/
class <?= $testFile->getName() ?> extends Unit
{
    use Specify;
<?php if($useAccessibleTrait===true):?>
    use AccessibleMethodTrait;
<?php endif;?>
<?php if($app->getTesterClass()):?>
	/**
	* @var <?=\yii\helpers\StringHelper::basename($app->getTesterClass())?>
	*/
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([]);
    }
<?php else:?>
    protected function _before()
    {
         //Initialize test
    }
<?php endif;?>

<?php foreach ($methods as $method=>$params):?>
    /**
    * Test for <?=$method.PHP_EOL?>
    * @see \<?=$params['signature'].PHP_EOL?>
    **/
    public function test<?=ucfirst($method)?>()
    {
        $this->markTestIncomplete();
        /**
         * TODO: test <?=$params['signature'].PHP_EOL?>
        **/
        $result = $this->getTarget()-><?=$method?>(<?=$params['required']?>);
    }
<?php endforeach;?>

    /**
    * @return <?=$reflection->getShortName()?>|object
    **/
    private function getTarget()
    {
         return \Yii::createObject([
         'class'=><?=$reflection->getShortName()?>::class,
         ],[]);
    }

    private function getStub()
    {
        return Stub::make(<?=$reflection->getShortName()?>::class,[

        ],$this);
    }
}