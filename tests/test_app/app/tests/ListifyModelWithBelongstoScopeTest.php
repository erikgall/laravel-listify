<?php

require_once __DIR__ . '/../../../../src/lookitsatravis/Listify/Exceptions/ListifyException.php';
require_once __DIR__ . '/../../../../src/lookitsatravis/Listify/Exceptions/NullForeignKeyException.php';
require_once __DIR__ . '/../../../../src/lookitsatravis/Listify/Exceptions/NullScopeException.php';

use Way\Tests\Assert;

class ListifyModelWithBelongstoScopeTest extends ListifyBaseTest {

    protected $model = 'FooWithBelongstoScopeA';
    protected $modelScopeValue = "foo_with_belongsto_scope_b_id = 1";

    private $modelB = 'FooWithBelongstoScopeB';

    private $foreignKeyId;

    public function setUp()
    {
        //This is the record that model A will belong to in order to test the scope
        $foo = App::make($this->modelB);
        $foo->name = 'BelongsToExample';
        $foo->save();

        $this->foreignKeyId = $foo->id;

        $this->belongsToFunction = 'foo_with_belongsto_scope_b';
        $this->belongsToObject = $foo;

        parent::setUp();

        //Now we setup the secondary records which will be out of scope and should remain unchanged throughout modification
        
        for($i = 1; $i <= 10; $i++)
        {
            $foo = App::make($this->model);
            $foo->name = $this->model . '-test-' . $i;
            $foo->foo_with_belongsto_scope_b_id = 99;
            $foo->save();
        }
    }

    /**
     * @expectedException lookitsatravis\Listify\Exceptions\NullScopeException
     */
    public function test_passInNullScopeThrowsError()
    {
        $model = $this->model;
        $foo = new $model();
        $foo->name = "FooHasNullScope";
        $foo->setListifyConfig('scope', NULL);
        $foo->save();
    }

    /**
     * @expectedException lookitsatravis\Listify\Exceptions\NullForeignKeyException
     */
    public function test_passInNullScopeWithForeignKeyThrowsError()
    {
        $model = $this->model;
        $foo = new $model();
        $foo->name = "FooHasNoForeignKey";
        $foo->save();
    }

    //The whole point of this is to validate that the secondary model (that shares the table) is not modified when manipulating the primary model. The scope should prevent that, so we validate that the secondary model has not changed after each test.
    protected function childAssertion()
    {
        $this->reloadFoos();
        $this->reloadBFoos();

        $position = 1;
        foreach($this->foos as $foo)
        {
            Assert::eq($this->foreignKeyId, $foo->foo_with_belongsto_scope_b_id);
            $position++;
        }

        $position = 1;
        foreach($this->bfoos as $bfoo)
        {
            Assert::eq($position, $bfoo->getListifyPosition());
            $position++;
        }
    }

    protected function reloadFoos()
    {
        $this->foos = App::make($this->model)->whereRaw($this->modelScopeValue)->orderBy('id', "ASC")->get()->all();
    }

    private function reloadBFoos()
    {
        $this->bfoos = App::make($this->model)->whereRaw('foo_with_belongsto_scope_b_id = 99')->orderBy('id', "ASC")->get()->all();
    }
}