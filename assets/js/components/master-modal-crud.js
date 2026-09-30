/**
 * Master & Submaster Common Modal CRUD Handler
 * Handles Add/Edit Modal, Form Reset, jQuery Validation, and Ajax Submit
 */
(function ($) {
    'use strict';

    window.initMasterModalCrud = function (options) {
        const config = $.extend(true, {
            modalId: '',             // e.g. '#CityModal'
            formId: '',              // e.g. '#cityForm'
            storeUrl: '',            // e.g. SITE_URL + 'admin/submaster/city/store.php'
            pageNm: '',              // e.g. 'City'
            modalTitleId: '',        // defaults to modalId + 'Label' if empty
            openModalBtn: '.open_modal',
            editBtn: '',             // e.g. '.city_edit'
            rules: {},               // jQuery validation rules
            messages: {},            // jQuery validation messages
            dataTableSelector: '.data-table',
            onReset: null,           // callback on open add modal: function($form, $modal)
            onEditPopulate: null,    // callback on edit data fetched: function(data, $form, $modal)
            onSuccess: null          // callback on ajax save success: function(response, $form, $modal)
        }, options);

        const $modal = $(config.modalId);
        const $form = $(config.formId);
        const modalTitleId = config.modalTitleId || (config.modalId + 'Label');

        // Helper: Set values for select/TomSelect or normal inputs
        function resetFormField(el) {
            if (el.tomselect) {
                el.tomselect.setValue('');
            } else {
                $(el).val('').trigger('change');
            }
        }

        // Helper: Set specific value for an element
        function setFieldValue(name, value) {
            const $el = $form.find('[name="' + name + '"]');
            if (!$el.length) return;

            // Security check: HTML file input cannot have a programmatically set string value
            if ($el.is('input[type="file"]')) {
                $el.val('');
                return;
            }

            const el = $el[0];
            if (el.tomselect) {
                el.tomselect.setValue(value !== null && value !== undefined ? String(value) : '');
            } else if ($el.is(':checkbox')) {
                $el.prop('checked', Boolean(value));
            } else if ($el.is(':radio')) {
                $form.find('[name="' + name + '"][value="' + value + '"]').prop('checked', true);
            } else {
                $el.val(value !== null && value !== undefined ? value : '');
                if ($el.is('select')) {
                    $el.trigger('change');
                }
            }
        }

        // 1. Open Add Modal
        $(document).on('click', config.openModalBtn, function () {
            // Reset hidden and text inputs
            $form.find('input[type="hidden"]').val('');
            $form[0].reset();

            // Clear TomSelect dropdowns if any
            $form.find('select').each(function () {
                if (this.tomselect) {
                    this.tomselect.setValue('');
                }
            });

            // Reset validation errors
            if ($form.data('validator')) {
                $form.validate().resetForm();
            }
            $form.find('.is-invalid').removeClass('is-invalid');
            // Remove only validation error messages (not asterisk spans inside form labels)
            $form.find('span.text-danger:not(.form-label span), label.text-danger').remove();

            // Set Title
            $(modalTitleId).text('Add ' + config.pageNm);

            // Custom onReset callback
            if (typeof config.onReset === 'function') {
                config.onReset($form, $modal);
            }

            $modal.modal('show');
            if (window.lucide) lucide.createIcons();
        });

        // 2. Form Validation & Submit Handling
        $form.validate({
            rules: config.rules,
            messages: config.messages,
            errorElement: 'span',
            errorClass: 'text-danger',
            // highlight: function (element) {
            //     // /$(element).addClass('is-invalid');
            // },
            // unhighlight: function (element) {
            //     $(element).removeClass('is-invalid');
            // },
            errorPlacement: function (error, element) {
                if (element.hasClass('tomselected') || element.next('.ts-wrapper').length) {
                    error.insertAfter(element.next('.ts-wrapper'));
                } else if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function (form) {
                const $button = $form.find('#submitBtn, button[type="submit"]');
                const $text = $form.find('#submitText');
                const $loader = $form.find('#submitLoader');
                const originalText = $text.length ? $text.text() : $button.text();

                if (typeof config.beforeSubmit === 'function') {
                    config.beforeSubmit($form, form);
                }

                const hasFileInput = $form.find('input[type="file"]').length > 0;
                let postData, contentTypeVal, processDataVal;

                if (hasFileInput) {
                    postData = new FormData(form);
                    contentTypeVal = false;
                    processDataVal = false;
                } else {
                    postData = $form.serialize();
                    contentTypeVal = 'application/x-www-form-urlencoded; charset=UTF-8';
                    processDataVal = true;
                }

                $.ajax({
                    url: config.storeUrl,
                    type: 'POST',
                    data: postData,
                    contentType: contentTypeVal,
                    processData: processDataVal,
                    dataType: 'json',
                    beforeSend: function () {
                        $button.prop('disabled', true);
                        if ($text.length) {
                            $text.text('Saving...');
                        }
                        if ($loader.length) {
                            $loader.removeClass('d-none');
                        }
                    },
                    success: function (response) {
                        if (response.status === true) {
                            if (typeof showToast === 'function') {
                                showToast(response.message, 'success');
                            }

                            $form[0].reset();
                            $form.find('input[type="hidden"]').val('');
                            if ($form.data('validator')) {
                                $form.validate().resetForm();
                            }

                            $modal.modal('hide');

                            if (config.dataTableSelector && $(config.dataTableSelector).length && $.fn.DataTable.isDataTable(config.dataTableSelector)) {
                                $(config.dataTableSelector).DataTable().ajax.reload(null, false);
                            }

                            if (typeof config.onSuccess === 'function') {
                                config.onSuccess(response, $form, $modal);
                            }
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(response.message || 'Something went wrong.', 'error');
                            }
                        }
                    },
                    error: function (xhr) {
                        console.error('Master AJAX error:', xhr.responseText);
                        if (typeof showToast === 'function') {
                            showToast('Something went wrong. Please try again.', 'error');
                        }
                    },
                    complete: function () {
                        $button.prop('disabled', false);
                        if ($text.length) {
                            $text.text(originalText || 'Submit');
                        }
                        if ($loader.length) {
                            $loader.addClass('d-none');
                        }
                    }
                });
                return false;
            }
        });

        // 3. Edit Record Handling
        if (config.editBtn) {
            $(document).on('click', config.editBtn, function () {
                const id = $(this).data('id');
                if (!id) return;

                $.ajax({
                    url: config.storeUrl,
                    type: 'GET',
                    data: {
                        id: id,
                        action: 'edit'
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === true && response.data) {
                            const rec = response.data;

                            // Reset validation first
                            if ($form.data('validator')) {
                                $form.validate().resetForm();
                            }
                            $form.find('.is-invalid').removeClass('is-invalid');
                            $form.find('span.text-danger:not(.form-label span), label.text-danger').remove();

                            // Auto populate matching fields
                            $.each(rec, function (key, val) {
                                setFieldValue(key, val);
                            });

                            $(modalTitleId).text('Edit ' + config.pageNm);

                            // Custom callback for cascading selects (e.g. states, cities, special logic)
                            if (typeof config.onEditPopulate === 'function') {
                                config.onEditPopulate(rec, $form, $modal);
                            }

                            $modal.modal('show');
                            if (window.lucide) lucide.createIcons();
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(response.message || 'Failed to fetch details.', 'error');
                            }
                        }
                    },
                    error: function (xhr) {
                        console.error('Edit fetch error:', xhr.responseText);
                        if (typeof showToast === 'function') {
                            showToast('Something went wrong. Please try again.', 'error');
                        }
                    }
                });
            });
        }
    };
})(jQuery);
