<?php

namespace Kreetancraft\Media\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A model from another package, deliberately WITHOUT HasMediaAttachments.
 *
 * This is the case MediaImageResolver exists for: a package that ships no image
 * handling cannot apply this package's trait without making it a hard
 * dependency, so its models must resolve images without inheriting anything.
 */
class Article extends Model
{
    protected $guarded = [];
}
