<?php declare(strict_types=1);

namespace FatCode\OpenApi\Annotation\PathItem;

use FatCode\Annotation\Target;
use FatCode\OpenApi\Annotation\Parameter;

/**
 * @Annotation
 * @Target(Target::TARGET_ANNOTATION)
 */
class QueryParameter extends Parameter
{
    public $in = 'query';
}
