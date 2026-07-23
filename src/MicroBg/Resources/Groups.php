<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Enum\GroupDeleteMode;
use Ux2Dev\Microinvest\Enum\GroupModule;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * The partner and item group trees.
 *
 * Paths encode the tree three letters per level ('AAA', 'AAAAAB', ...); '-1' is
 * the service group, which cannot be deleted and collects orphaned records.
 */
final class Groups extends Resource
{
    /** @return ResultList<NomenclatureGroupResult> */
    public function list(GroupModule $module): ResultList
    {
        return $this->transport->callList('getGroups', ['Module' => $module->value], NomenclatureGroupResult::class);
    }

    /**
     * Create a group. Give either a parentId or a Path to nest it; give
     * neither for a top-level group.
     */
    public function create(
        GroupModule $module,
        string $name,
        ?int $parentId = null,
        ?string $path = null,
    ): NomenclatureGroupResult {
        return $this->transport->callOne(
            'insertGroup',
            ['Module' => $module->value, 'parentId' => $parentId, 'Path' => $path],
            ['Name' => $name],
            NomenclatureGroupResult::class,
        );
    }

    public function rename(GroupModule $module, int $id, string $name): NomenclatureGroupResult
    {
        return $this->transport->callOne(
            'renameGroup',
            ['Module' => $module->value, 'Id' => $id],
            ['Name' => $name],
            NomenclatureGroupResult::class,
        );
    }

    /**
     * Delete a group and every subgroup under it. Mode All wipes the whole tree
     * except the service group.
     */
    public function delete(GroupModule $module, GroupDeleteMode $mode, ?int $id = null, ?string $path = null): void
    {
        $this->transport->call('deleteGroup', [
            'Module' => $module->value,
            'Mode' => $mode->value,
            'Id' => $id,
            'Path' => $path,
        ]);
    }
}
