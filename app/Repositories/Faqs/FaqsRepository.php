<?php

namespace App\Repositories\Faqs;

use App\Models\Faq;
use App\Repositories\Base\BaseRepository;

class FaqsRepository extends BaseRepository implements FaqsInterface {
    public function __construct(Faq $model) {
        parent::__construct($model,'faq');
    }
}
