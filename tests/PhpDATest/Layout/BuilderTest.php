<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Marco Muths
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PhpDATest\Layout;

use PhpDA\Entity\AnalysisCollection;
use PhpDA\Layout\Builder;
use PhpParser\Node\Name;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    const LE = '_e_';

    const LEIM = '_eIm_';

    const LEEX = '_eEx_';

    const LETU = '_eTu_';

    const LEUS = '_eUs_';

    const LENS = '_eNs_';

    const LV = '_v_';

    const LVUS = '_vUs_';

    const LVNS = '_vNs_';

    /** @var Builder */
    protected $fixture;

    /** @var \PhpDA\Layout\Helper\GroupGenerator | \Mockery\MockInterface */
    protected $groupGenerator;

    /** @var \PhpDA\Layout\GraphViz | \Mockery\MockInterface */
    protected $graphViz;

    /** @var \PhpDA\Layout\Graph | \Mockery\MockInterface */
    protected $graph;

    /** @var \PhpDA\Entity\Adt | \Mockery\MockInterface */
    protected $adt;

    /** @var \Fhaculty\Graph\Vertex | \Mockery\MockInterface */
    protected $rootVertex;

    protected function setUp()
    {
        $this->groupGenerator = \Mockery::mock('PhpDA\Layout\Helper\GroupGenerator');
        $this->graph = \Mockery::mock('PhpDA\Layout\Graph');
        $this->graph->shouldReceive('setLayout')->with(array());
        $this->graphViz = \Mockery::mock('PhpDA\Layout\GraphViz');
        $this->graphViz->shouldReceive('getGraph')->andReturn($this->graph);

        $this->adt = \Mockery::mock('PhpDA\Entity\Adt');

        $this->fixture = new Builder($this->graphViz, $this->groupGenerator);
    }

    public function testDelegatingGroupLengthMutatorToGroupGenerator()
    {
        $this->groupGenerator->shouldReceive('setGroupLength')->once()->with(4);
        $this->fixture->setGroupLength(4);
    }

    public function testFluentInterfaceForCreating()
    {
        $this->groupGenerator->shouldReceive('getGroups')->andReturn(array());
        $this->graph->shouldReceive('setLayout');
        $this->graphViz->shouldReceive('setGroups');
        $this->graphViz->shouldReceive('setGroupLayout');

        $this->assertSame($this->fixture, $this->fixture->create());
    }

    public function testDelegatingGraphLayoutGeneratedGroups()
    {
        $layout = \Mockery::mock('PhpDA\Layout\LayoutInterface');
        $layout->shouldReceive('getGraph')->once()->andReturn(array('foo'));
        $layout->shouldReceive('getGroup')->once()->andReturn(array('bar'));

        $this->groupGenerator->shouldReceive('getGroups')->once()->andReturn(array('baz'));

        $this->graph->shouldReceive('setLayout')->once()->with(array('foo'));
        $this->graphViz->shouldReceive('setGroups')->once()->with(array('baz'));
        $this->graphViz->shouldReceive('setGroupLayout')->once()->with(array('bar'));

        $this->fixture->setLayout($layout);

        $this->assertSame($this->fixture, $this->fixture->create());
    }

    public function testAccessGraphViz()
    {
        $this->assertSame($this->graphViz, $this->fixture->getGraphViz());
    }

    private function prepareDependencyCreation()
    {
        $this->groupGenerator->shouldReceive('getGroups')->andReturn(array());
        $this->graph->shouldReceive('setLayout');
        $this->graphViz->shouldReceive('setGroups');
        $this->graphViz->shouldReceive('setGroupLayout');

        $layout = \Mockery::mock('PhpDA\Layout\LayoutInterface');
        $layout->shouldReceive('getGraph')->andReturn(array());
        $layout->shouldReceive('getGroup')->andReturn(array());

        $layout->shouldReceive('getVertex')->andReturn(array(self::LV));
        $layout->shouldReceive('getVertexUnsupported')->andReturn(array(self::LVUS));
        $layout->shouldReceive('getVertexNamespacedString')->andReturn(array(self::LVNS));

        $layout->shouldReceive('getEdge')->andReturn(array(self::LE));
        $layout->shouldReceive('getEdgeImplement')->andReturn(array(self::LEIM));
        $layout->shouldReceive('getEdgeExtend')->andReturn(array(self::LEEX));
        $layout->shouldReceive('getEdgeTraitUse')->andReturn(array(self::LETU));
        $layout->shouldReceive('getEdgeUnsupported')->andReturn(array(self::LEUS));
        $layout->shouldReceive('getEdgeNamespacedString')->andReturn(array(self::LENS));

        $this->fixture->setLayout($layout);

        $collection = new AnalysisCollection;
        $analysis = \Mockery::mock('PhpDA\Entity\Analysis');
        $analysis->shouldReceive('getAdts')->andReturn(array($this->adt));
        $collection->attach($analysis);

        $this->fixture->setAnalysisCollection($collection);
    }

    /**
     * @param string                        $fqn
     * @param Name | \Mockery\MockInterface $root
     * @param bool                          $hasEdge
     * @param string                        $vertexLayout
     * @param string                        $edgeLayout
     * @return Name | \Mockery\MockInterface
     */
    private function createName(
        $fqn,
        Name $root = null,
        $hasEdge = false,
        $vertexLayout = null,
        $edgeLayout = self::LE
    ) {
        /** @var Name | \Mockery\MockInterface $name */
        $name = \Mockery::mock('PhpParser\Node\Name');
        $name->shouldReceive('toString')->andReturn($fqn);
        $name->parts = explode('\\', $fqn);
        $vertex = \Mockery::mock('Fhaculty\Graph\Vertex');
        $this->graph->shouldReceive('createVertex')->once()->with($fqn, true)->andReturn($vertex);
        $this->groupGenerator->shouldReceive('getIdFor')->once()->with($name)->andReturn(5);
        $vertex->shouldReceive('setGroup')->once()->with(5);
        $vertex->shouldReceive('getLayout')->andReturn(array());
        $vertex->shouldReceive('setLayout')->once()->with(array(self::LV, 'group' => 5));

        if ($root) {
            $vertex->shouldReceive('setLayout')->once()->with($vertexLayout ? array($vertexLayout) : array());
            if ($root->parts !== $name->parts) {
                $this->rootVertex->shouldReceive('hasEdgeTo')->once()->with($vertex)->andReturn($hasEdge);
                if (!$hasEdge) {
                    $edge = \Mockery::mock('Fhaculty\Graph\Edge\Directed');
                    $edge->shouldReceive('setLayout')->once()->with(array($edgeLayout));
                    $this->rootVertex->shouldReceive('createEdgeTo')->once()->with($vertex)->andReturn($edge);
                }
            }
        } else {
            $this->rootVertex = $vertex;
        }

        return $name;
    }

    public function testDependencyCreationInCallMode()
    {
        $this->fixture->setCallMode();

        $this->prepareDependencyCreation();

        $declared = $this->createName('Dec\\Name');

        $this->adt->shouldReceive('getDeclaredNamespace')->once()->andReturn($declared);

        $this->adt->shouldReceive('getImplementedNamespaces')->never();
        $this->adt->shouldReceive('getExtendedNamespaces')->never();
        $this->adt->shouldReceive('getUsedTraitNamespaces')->never();
        $this->adt->shouldReceive('getUsedNamespaces')->never();

        $called1 = $this->createName('Called\\Name1', $declared);
        $called2 = $this->createName('Called\\Name2', $declared);

        $uns1 = $this->createName('Uns\\Name1', $declared, false, self::LVUS, self::LEUS);
        $uns2 = $this->createName('Uns\\Name2', $declared, false, self::LVUS, self::LEUS);

        $string1 = $this->createName('String\\Name1', $declared, true, self::LVNS, self::LENS);
        $string2 = $this->createName('String\\Name2', $declared, false, self::LVNS, self::LENS);

        $this->adt->shouldReceive('getCalledNamespaces')->once()->andReturn(array($called1, $called2));
        $this->adt->shouldReceive('getUnsupportedStmts')->once()->andReturn(array($uns1, $uns2));
        $this->adt->shouldReceive('getNamespacedStrings')->once()->andReturn(array($string1, $string2));

        $this->assertSame($this->fixture, $this->fixture->create());
    }

    public function testDependencyCreationNotInCallMode()
    {
        $this->prepareDependencyCreation();

        $declared = $this->createName('Dec\\Name');

        $this->adt->shouldReceive('getDeclaredNamespace')->once()->andReturn($declared);

        $this->adt->shouldReceive('getCalledNamespaces')->never();

        $imp1 = $this->createName('Imp\\Name1', $declared, false, null, self::LEIM);
        $imp2 = $this->createName('Imp\\Name2', $declared, false, null, self::LEIM);

        $ext = $this->createName('Ext\\Name1', $declared, false, null, self::LEEX);

        $trait1 = $this->createName('Trait\\Name1', $declared, false, null, self::LETU);
        $trait2 = $this->createName('Trait\\Name2', $declared, false, null, self::LETU);

        $used1 = $this->createName('Used\\Name1', $declared);
        $used2 = $this->createName('Used\\Name2', $declared);
        $used3 = $this->createName('Used\\Name2', $declared, true);
        $used4 = $this->createName('Used\\Name2', $declared);

        $uns1 = $this->createName('Uns\\Name1', $declared, true, self::LVUS, self::LEUS);
        $uns2 = $this->createName('Uns\\Name2', $declared, false, self::LVUS, self::LEUS);

        $string1 = $this->createName('String\\Name1', $declared, false, self::LVNS, self::LENS);
        $string2 = $this->createName('String\\Name2', $declared, false, self::LVNS, self::LENS);

        $meta = \Mockery::mock('PhpDa\Entity\Meta');
        $this->adt->shouldReceive('getMeta')->andReturn($meta);

        $meta->shouldReceive('getImplementedNamespaces')->once()->andReturn(array($imp1, $imp2));
        $meta->shouldReceive('getExtendedNamespaces')->once()->andReturn(array($ext));
        $meta->shouldReceive('getUsedTraitNamespaces')->once()->andReturn(array($trait1, $trait2));
        $this->adt->shouldReceive('getUsedNamespaces')->once()->andReturn(array($used1, $used2, $used3, $used4));
        $this->adt->shouldReceive('getUnsupportedStmts')->once()->andReturn(array($uns1, $uns2));
        $this->adt->shouldReceive('getNamespacedStrings')->once()->andReturn(array($string1, $string2));

        $this->assertSame($this->fixture, $this->fixture->create());
    }
}
