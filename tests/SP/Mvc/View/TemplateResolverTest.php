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

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Mvc\View\TemplateResolver;

/**
 * Class TemplateResolverTest
 */
#[Group('unitary')]
class TemplateResolverTest extends TestCase
{
    private ThemeInterface|MockObject $theme;
    private TemplateResolver          $templateResolver;

    /**
     * @throws FileNotFoundException
     */
    public function testGetTemplateFor()
    {
        $vfsStreamDirectory = vfsStream::setup(
            'template_dir',
            755,
            ['base_dir' => ['test_template.inc' => 'a_content']]
        );

        $this->theme
            ->expects($this->once())
            ->method('getViewsPath')
            ->willReturn($vfsStreamDirectory->url());

        $out = $this->templateResolver->getTemplateFor('base_dir', 'test_template');

        $this->assertEquals($vfsStreamDirectory->url() . '/base_dir/test_template.inc', $out);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->theme = $this->createMock(ThemeInterface::class);
        $this->templateResolver = new TemplateResolver($this->theme);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testGetTemplateForWithNoPermissions()
    {
        $vfsStreamDirectory = vfsStream::setup(
            'root_dir',
            755,
            ['base_dir' => []]
        );

        $this->theme
            ->expects($this->once())
            ->method('getViewsPath')
            ->willReturn($vfsStreamDirectory->url());

        $this->expectException(FileNotFoundException::class);

        $this->templateResolver->getTemplateFor('base_dir', 'test_template');
    }
}
