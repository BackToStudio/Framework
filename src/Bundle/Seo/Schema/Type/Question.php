<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class Question extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Question');
    }

    /** @return $this */
    public function name(string $question): static
    {
        return $this->set('name', $question);
    }

    /** @return $this */
    public function acceptedAnswer(SchemaType $answer): static
    {
        return $this->set('acceptedAnswer', $answer);
    }

    
    public function suggestedAnswer(array $answers): static
    {
        return $this->set('suggestedAnswer', $answers);
    }
}
