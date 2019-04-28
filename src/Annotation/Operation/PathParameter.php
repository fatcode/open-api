<?php declare(strict_types=1);

namespace FatCode\OpenApi\Annotation\Route;

use FatCode\Annotation\Target;
use FatCode\OpenApi\Annotation\Parameter;

/**
 * @Annotation
 * @Target(Target::TARGET_ANNOTATION)
 */
class PathParameter extends Parameter
{
    public $in = 'path';
}