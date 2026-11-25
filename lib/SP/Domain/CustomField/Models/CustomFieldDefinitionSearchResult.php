<?php
declare(strict_types=1);
/* Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org */

namespace SP\Domain\CustomField\Models;

/** A custom-field definition enriched with its type label for search results. */
final class CustomFieldDefinitionSearchResult extends CustomFieldDefinition
{
    protected ?string $typeName = null;

    public function getTypeName(): ?string
    {
        return $this->typeName;
    }
}
