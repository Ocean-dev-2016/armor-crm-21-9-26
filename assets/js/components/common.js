$(document).ready(function () {
    $(document).on('click', '.delete-record', function () {
        let id = $(this).data('id');
        let tbl = $(this).data('tbl');
        if (!id) {
            showToast('Invalid record ID.', 'error');
            return;
        }
        Swal.fire({
            title: 'Are you sure?',
            text: "you want to delete this record?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0e5a6c',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: SITE_URL + 'component/common.php',
                    type: 'GET',
                    data: {
                        id: id,
                        action: 'delete',
                        tbl: tbl
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        console.log('Deleting record:', id);
                    },
                    success: function (response) {
                        if (response.status === true) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#0e5a6c'
                            });
                            $('.data-table').DataTable().ajax.reload(null, false);
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function (xhr) {
                        console.log(xhr.responseText);
                        showToast('Something went wrong. Please try again.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('change', '.change_status', function () {
        let checkbox = $(this);

        let id = checkbox.data('id');
        let tbl = checkbox.data('tbl');
        let status = checkbox.is(':checked') ? 1 : 0;

        if (!id) {
            showToast('Invalid record ID.', 'error');
            checkbox.prop('checked', !checkbox.is(':checked'));
            return;
        }

        if (!tbl) {
            showToast('Invalid table name.', 'error');
            checkbox.prop('checked', !checkbox.is(':checked'));
            return;
        }

        $.ajax({
            url: SITE_URL + 'component/common.php',
            type: 'POST',
            data: {
                action: 'status',
                id: id,
                tbl: tbl,
                status: status
            },
            dataType: 'json',
            beforeSend: function () {
                checkbox.prop('disabled', true);
            },
            success: function (response) {
                console.log('Status Response:', response);
                if (response.status === true) {
                    showToast(response.message, 'success');
                } else {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    showToast(response.message, 'error');
                }
            },
            error: function (xhr) {
                console.log('AJAX Status:', xhr.status);
                console.log('Response:', xhr.responseText);
                checkbox.prop('checked', !checkbox.is(':checked'));
                showToast('Something went wrong. Please try again.', 'error');
            },
            complete: function () {
                checkbox.prop('disabled', false);
            }
        });
    });

    window.loadStatesByCountry = function (country_id, selectedStateId, callback) {
        if (!country_id) {
            let select = $('.state_base_city')[0];
            if (select && select.tomselect) {
                select.tomselect.clear();
                select.tomselect.clearOptions();
                select.tomselect.addOption({ value: '', text: 'Select a State', $order: 1 });
                select.tomselect.setValue('');
            } else {
                $('.state_base_city').html('<option value="">Select a State</option>');
            }
            if (typeof callback === 'function') callback();
            return;
        }

        $.ajax({
            url: SITE_URL + 'component/common.php',
            type: 'GET',
            data: {
                country_id: country_id,
                action: 'country_base_state'
            },
            dataType: 'json',
            beforeSend: function () {
                console.log('Fetching states based on country:', country_id);
            },
            success: function (response) {
                if (response.status === true) {
                    let select = $('.state_base_city')[0];

                    if (select && select.tomselect) {
                        select.tomselect.clear();
                        select.tomselect.clearOptions();
                        select.tomselect.addOption({ value: '', text: 'Select a State', $order: 1 });
                        let sOrder = 2;
                        $.each(response.data, function (key, value) {
                            select.tomselect.addOption({ value: String(value.id), text: value.name, $order: sOrder++ });
                        });
                        if (selectedStateId) {
                            select.tomselect.setValue(String(selectedStateId));
                        } else {
                            select.tomselect.setValue('');
                        }
                        select.tomselect.refreshOptions(false);
                    } else {
                        let option = '<option value="">Select a State</option>';
                        $.each(response.data, function (key, value) {
                            let selected = (selectedStateId && selectedStateId == value.id) ? ' selected' : '';
                            option += '<option value="' + value.id + '"' + selected + '>' + value.name + '</option>';
                        });
                        $('.state_base_city').html(option);
                        if (selectedStateId) {
                            $('.state_base_city').val(selectedStateId).trigger('change');
                        }
                    }
                    if (typeof callback === 'function') {
                        callback(response.data);
                    }
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                showToast('Something went wrong. Please try again.', 'error');
            }
        });
    };

    window.loadCitiesByState = function (state_id, selectedCityId, callback) {
        if (!state_id) {
            let select = $('.city_base_state')[0] || $('#select-city')[0];
            if (select && select.tomselect) {
                select.tomselect.clear();
                select.tomselect.clearOptions();
                select.tomselect.addOption({ value: '', text: 'Select City', $order: 1 });
                select.tomselect.setValue('');
            } else {
                $('.city_base_state, #select-city').html('<option value="">Select City</option>');
            }
            if (typeof callback === 'function') callback();
            return;
        }

        $.ajax({
            url: SITE_URL + 'component/common.php',
            type: 'GET',
            data: {
                state_id: state_id,
                action: 'state_base_city'
            },
            dataType: 'json',
            beforeSend: function () {
                console.log('Fetching cities based on state:', state_id);
            },
            success: function (response) {
                if (response.status === true) {
                    let select = $('.city_base_state')[0] || $('#select-city')[0];

                    if (select && select.tomselect) {
                        select.tomselect.clear();
                        select.tomselect.clearOptions();
                        select.tomselect.addOption({ value: '', text: 'Select City', $order: 1 });
                        let cOrder = 2;
                        $.each(response.data, function (key, value) {
                            select.tomselect.addOption({ value: String(value.id), text: value.name, $order: cOrder++ });
                        });
                        if (selectedCityId) {
                            select.tomselect.setValue(String(selectedCityId));
                        } else {
                            select.tomselect.setValue('');
                        }
                        select.tomselect.refreshOptions(false);
                    } else {
                        let option = '<option value="">Select City</option>';
                        $.each(response.data, function (key, value) {
                            let selected = (selectedCityId && selectedCityId == value.id) ? ' selected' : '';
                            option += '<option value="' + value.id + '"' + selected + '>' + value.name + '</option>';
                        });
                        $('.city_base_state, #select-city').html(option);
                        if (selectedCityId) {
                            $('.city_base_state, #select-city').val(selectedCityId).trigger('change');
                        }
                    }
                    if (typeof callback === 'function') {
                        callback(response.data);
                    }
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function (xhr) {
                console.log(xhr.responseText);
                showToast('Something went wrong. Please try again.', 'error');
            }
        });
    };

    $(document).on('change', '.country_base_state', function () {
        let country_id = $(this).val();
        loadStatesByCountry(country_id, null);
    });

    $(document).on('change', '.state_base_city', function () {
        let state_id = $(this).val();
        loadCitiesByState(state_id, null);
    });

    // ==========================================
    // Common Print Record Handler
    // ==========================================
    $(document).on('click', '#btnPrintRecord', function () {
        let tbl = $(this).data('tbl') || '';
        let fields = $(this).data('fields') || 'name';
        let pageName = $(this).data('page-name') || '';
        let module = $(this).data('module') || tbl;

        if (!tbl) {
            showToast('Table name not configured for print.', 'error');
            return;
        }

        let searchVal = '';
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('.data-table')) {
            searchVal = $('.data-table').DataTable().search();
        }

        let url = SITE_URL + 'component/common.php?action=print_data'
            + '&tbl=' + encodeURIComponent(tbl)
            + '&fields=' + encodeURIComponent(fields)
            + '&pageNm=' + encodeURIComponent(pageName)
            + '&module=' + encodeURIComponent(module);

        if (searchVal) {
            url += '&search=' + encodeURIComponent(searchVal);
        }

        window.open(url, '_blank');
    });

    // ==========================================
    // Common Excel Export Handler
    // ==========================================
    $(document).on('click', '#btnExportExcel', function () {
        let tbl = $(this).data('tbl') || '';
        let fields = $(this).data('fields') || 'name';
        let pageName = $(this).data('page-name') || '';
        let module = $(this).data('module') || tbl;

        if (!tbl) {
            showToast('Table name not configured for excel export.', 'error');
            return;
        }

        let searchVal = '';
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('.data-table')) {
            searchVal = $('.data-table').DataTable().search();
        }

        let url = SITE_URL + 'component/common.php?action=export_excel'
            + '&tbl=' + encodeURIComponent(tbl)
            + '&fields=' + encodeURIComponent(fields)
            + '&pageNm=' + encodeURIComponent(pageName)
            + '&module=' + encodeURIComponent(module);

        if (searchVal) {
            url += '&search=' + encodeURIComponent(searchVal);
        }

        window.location.href = url;
    });
});