# Phpunit

**You may fork and modify it as you wish**.

Any suggestions are welcomed.

## How to use

* Extend **\PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase** or **\PrecisionSoft\Symfony\Phpunit\TestCase\AbstractKernelTestCase** if you need the symfony kernel.
* **PrecisionSoft\Symfony\Phpunit\Mock** contains generic mocks.

## Example

```php
namespace Acme\Test\Foo\Service;

use Acme\Foo\Repository\FooRepository;
use Acme\Foo\Service\CreateService;
use PrecisionSoft\Symfony\Phpunit\Mock\ManagerRegistryMock;
use PrecisionSoft\Symfony\Phpunit\MockDto;
use PrecisionSoft\Symfony\Phpunit\TestCase\AbstractTestCase;

final class CreateServiceTest extends AbstractTestCase
{
    public static function getMockDto(): MockDto
    {
        return new MockDto(
            CreateService::class,
            [
                ManagerRegistryMock::class,
                new MockDto(FooRepository::class),
                'staticDependency',
            ],
            true
        );
    }

    public function testCreate(): void
    {
    }
}
```

## Dev

```shell
git clone git@github.com:precision-soft/symfony-phpunit.git
cd phpunit

./dc build && ./dc up -d
```

