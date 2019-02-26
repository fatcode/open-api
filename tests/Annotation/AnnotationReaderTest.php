<?php declare(strict_types=1);

namespace IgniTest\OpenApi\Annotation;

use Igni\OpenApi\Annotation\AnnotationReader;
use IgniTest\OpenApi\Fixtures\PetShopApplication;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AnnotationReaderTest extends TestCase
{
    public function testReadFromClass() : void
    {
        $reflectionClass = new ReflectionClass(PetShopApplication::class);
        $reader = new AnnotationReader();
        $annotations = $reader->readFromClass($reflectionClass);

        $a = 1;
    }
}