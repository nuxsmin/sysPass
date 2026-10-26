<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace SP\Tests\Mvc\View;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\OutputHandlerInterface;
use SP\Mvc\View\Template;
use SP\Mvc\View\TemplateResolverInterface;
use SP\Tests\UnitaryTestCase;

/**
 * Class TemplateTest
 */
#[Group('unitary')]
class TemplateTest extends UnitaryTestCase
{

    private Template                             $template;
    private MockObject|OutputHandlerInterface    $outputHandler;
    private MockObject|TemplateResolverInterface $templateResolver;

    public function testUpgrade()
    {
        $this->template->upgrade();

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testAddPartial()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('_partials', 'a_partial_template');

        $this->template->addPartial('a_partial_template');
    }

    /**
     * @throws FileNotFoundException
     */
    public function testIncludePartial()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('_partials', 'a_partial_template')
            ->willReturn('partial_template_path');

        $out = $this->template->includePartial('a_partial_template');

        $this->assertEquals('partial_template_path', $out);
    }

    public function testGetBase()
    {
        $this->assertEquals('test', $this->template->getBase());
    }

    public function testRemove()
    {
        $this->template->remove('test');

        $this->expectNotToPerformAssertions();
    }

    public function testAppend()
    {
        $this->template->append('a_variable', 'a_value');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testAddTemplate()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('test', 'a_template');

        $this->template->addTemplate('a_template');
    }

    /**
     * @throws FileNotFoundException
     */
    public function testSetLayout()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('_layouts', 'a_layout');

        $this->template->setLayout('a_layout');
    }

    /**
     * @throws FileNotFoundException
     */
    public function testRender()
    {
        $this->outputHandler
            ->expects($this->once())
            ->method('bufferedContent')
            ->willReturn('a_content');

        $this->templateResolver
            ->method('getTemplateFor')
            ->willReturn('test');

        $this->template->addTemplate('test');

        $out = $this->template->render();

        $this->assertEquals('a_content', $out);
    }

    public function testRenderWithNoTemplates()
    {
        $this->outputHandler
            ->expects($this->never())
            ->method('bufferedContent');

        $out = $this->template->render();

        $this->assertEquals('', $out);
    }

    public function testReset()
    {
        $this->template->reset();

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testAddContentTemplate()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('test', 'a_template');

        $this->template->addContentTemplate('a_template');
    }

    /**
     * @throws FileNotFoundException
     */
    public function testAddContentTemplateWithBase()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('a_base', 'a_template');

        $this->template->addContentTemplate('a_template', 'a_base');
    }

    public function testAssign()
    {
        $this->template->assign('key', 'value');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testIncludeTemplate()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('test', 'a_template')
            ->willReturn('template_path');

        $out = $this->template->includeTemplate('a_template');

        $this->assertEquals('template_path', $out);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testGetContentTemplates()
    {
        $this->templateResolver
            ->expects($this->once())
            ->method('getTemplateFor')
            ->with('test', 'a_template')
            ->willReturn('template_path');

        $this->template->addContentTemplate('a_template');

        $out = $this->template->getContentTemplates();

        $this->assertEquals(['a_template' => 'template_path'], $out);
    }

    public function testAssignWithScope()
    {
        $this->template->assignWithScope('key', 'value', 'test');

        $this->expectNotToPerformAssertions();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $icons = $this->createStub(ThemeIconsInterface::class);
        $uriContext = $this->createStub(UriContextInterface::class);
        $this->outputHandler = $this->createMock(OutputHandlerInterface::class);
        $this->templateResolver = $this->createMock(TemplateResolverInterface::class);

        $this->template = new Template(
            $this->outputHandler,
            $this->templateResolver,
            $icons,
            $uriContext,
            $this->config->getConfigData(),
            'test'
        );
    }
}
