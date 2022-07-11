<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an hashid Field.
 * @see FieldHashid
 * @since Symphony 3.0.0
 */
class EntryQueryHashidAdapter extends EntryQueryFieldAdapter
{
    /**
     * @see EntryQueryFieldAdapter::filterSingle()
     *
     * @param EntryQuery $query
     * @param string $filter
     * @return array
     */
    protected function filterSingle(EntryQuery $query, $filter)
    {
        General::ensureType([
            'filter' => ['var' => $filter, 'type' => 'string'],
        ]);
        if ($this->isFilterRegex($filter)) {
            return $this->createFilterRegexp($filter, ['value']);
        }
        return $this->createFilterEquality($filter, ['value']);
    }

    public function getSortColumns()
    {
        return ['value'];
    }
}
