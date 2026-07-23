<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * The partner and item group trees.
 *
 * Every method takes a module: 'Items' or 'Partners'. Paths encode the tree
 * three letters per level ('AAA', 'AAAAAB', ...); '-1' is the service group,
 * which cannot be deleted and collects orphaned records.
 */
final class Groups extends Resource
{
    /** @return ResultList<NomenclatureGroupResult> */
    public function list(string $module): ResultList
    {
        return $this->transport->callList('getGroups', ['Module' => $module], NomenclatureGroupResult::class);
    }

    /**
     * Create a group. Give either a parentId or a Path to nest it; give
     * neither for a top-level group.
     */
    public function create(
        string $module,
        string $name,
        ?int $parentId = null,
        ?string $path = null,
    ): NomenclatureGroupResult {
        return $this->transport->callOne(
            'insertGroup',
            ['Module' => $module, 'parentId' => $parentId, 'Path' => $path],
            ['Name' => $name],
            NomenclatureGroupResult::class,
        );
    }

    public function rename(string $module, int $id, string $name): NomenclatureGroupResult
    {
        return $this->transport->callOne(
            'renameGroup',
            ['Module' => $module, 'Id' => $id],
            ['Name' => $name],
            NomenclatureGroupResult::class,
        );
    }

    /**
     * Delete a group and every subgroup under it. Mode 'All' wipes the whole
     * tree except the service group.
     *
     * @param 'ById'|'ByPath'|'All' $mode
     */
    public function delete(string $module, string $mode, ?int $id = null, ?string $path = null): void
    {
        $this->transport->call('deleteGroup', [
            'Module' => $module,
            'Mode' => $mode,
            'Id' => $id,
            'Path' => $path,
        ]);
    }
}
