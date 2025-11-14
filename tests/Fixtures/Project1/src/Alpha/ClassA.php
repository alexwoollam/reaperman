<?php

declare(strict_types=1);

namespace Alpha;

final class ClassA
{
    public function call(): void
    {
        $this->used();
        \Alpha\used_func();
    }

    public function used(): void
    {
    }

    private function unusedPrivate(): void
    {
    }
}
