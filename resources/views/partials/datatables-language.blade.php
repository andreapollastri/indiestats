@php
    $paDatatablesLanguage = [
        'emptyTable' => __('datatables.empty_table'),
        'info' => __('datatables.info'),
        'infoEmpty' => __('datatables.info_empty'),
        'infoFiltered' => __('datatables.info_filtered'),
        'infoThousands' => __('datatables.info_thousands'),
        'decimal' => __('datatables.decimal'),
        'lengthMenu' => __('datatables.length_menu'),
        'loadingRecords' => __('datatables.loading_records'),
        'processing' => __('datatables.processing'),
        'search' => __('datatables.search'),
        'zeroRecords' => __('datatables.zero_records'),
        'paginate' => [
            'first' => __('datatables.paginate_first'),
            'last' => __('datatables.paginate_last'),
            'next' => __('datatables.paginate_next'),
            'previous' => __('datatables.paginate_previous'),
        ],
        'aria' => [
            'orderable' => __('datatables.aria_orderable'),
            'orderableReverse' => __('datatables.aria_orderable_reverse'),
            'orderableRemove' => __('datatables.aria_orderable_remove'),
            'sortAscending' => __('datatables.aria_sort_ascending'),
            'sortDescending' => __('datatables.aria_sort_descending'),
        ],
    ];
@endphp
<script type="application/json" id="pa-datatables-language">
@json($paDatatablesLanguage)
</script>
