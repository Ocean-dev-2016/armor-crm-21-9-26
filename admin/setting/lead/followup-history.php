<?php
require_once __DIR__ . '/../../../conn/db.php';
require_once __DIR__ . '/../../../conn/dbqry.php';
require_once __DIR__ . '/../../../conn/helper.php';
include BASE_PATH . '/include/header.php';

// Only Superadmin can access
if (!$isSuperadmin) {
    echo "<div class='container py-5 text-center'><h3 class='text-danger'>Access Denied</h3><p>Only Superadmin can access SaaS Platform Leads.</p><a href='" . SITE_URL . "' class='btn btn-primary'>Back to Home</a></div>";
    include BASE_PATH . '/include/footer.php';
    exit;
}

// Decode lead ID if provided
$encId = $_GET['id'] ?? '';
$leadId = 0;
if (!empty($encId)) {
    $leadId = decrypt_id($encId);
    if (!$leadId && is_numeric($encId)) {
        $leadId = (int)$encId;
    }
}

$lead = null;
if ($leadId > 0) {
    $lead = db_row("SELECT * FROM lead WHERE id = $leadId LIMIT 1");
}

// Active follow-up types & statuses for modal dropdowns
$leadFollowupTypes = db_rows("SELECT id, name FROM lead_followup_type WHERE status = 1 ORDER BY id ASC");
$leadStatuses = db_rows("SELECT id, name, color FROM lead_status WHERE status = 1 ORDER BY order_by ASC");

$pageNm = 'Lead Follow-up History';
$breadcrumbType = 'form';
$parentUrl = SITE_URL . 'lead';
$customName = $lead ? ($lead['business_name'] . ' (' . ($lead['lead_number'] ?: ('OI-' . $lead['id'])) . ')') : 'All Follow-up History';
include BASE_PATH . '/component/breadcrumb.php';
?>

<!-- Lead Summary Card (if specific lead) -->
<?php if ($lead): ?>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-16" style="width: 44px; height: 44px;">
                                <?= strtoupper(substr($lead['business_name'] ?? 'L', 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($lead['business_name'] ?? '') ?></h5>
                                <div class="text-muted fs-13 d-flex flex-wrap align-items-center gap-3 mt-1">
                                    <span><i data-lucide="user" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($lead['contact_name'] ?? '-') ?></span>
                                    <span><i data-lucide="phone" class="fs-13 align-middle text-muted me-1"></i><?= htmlspecialchars($lead['mobile_no'] ?? '-') ?></span>
                                    <span><i data-lucide="tag" class="fs-13 align-middle text-muted me-1"></i>Lead Status: <strong><?= htmlspecialchars(get_lead_stage_label($lead['lead_stage'] ?? '')) ?></strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-primary btn-sm btn-open-add-fu" data-id="<?= $lead['id'] ?>" data-name="<?= htmlspecialchars($lead['business_name'], ENT_QUOTES) ?>" data-stage="<?= htmlspecialchars($lead['lead_stage'] ?? '', ENT_QUOTES) ?>">
                                <i data-lucide="plus-circle" class="fs-14 align-middle me-1"></i> Add Follow-up
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Follow-up History Standard DataTable -->
<?php
$tbl = 'lead_followups';
$ajaxUrl = SITE_URL . 'admin/setting/lead/followup-history-ajax.php' . ($leadId > 0 ? '?lead_id=' . $leadId : '');
if ($leadId > 0) {
    $tableHeaders = [
        'Sr No.',
        'Follow-up Type',
        'Follow-up Date & Time',
        'Lead Status',
        'Discussion Remarks',
        'Response',
        'Followup Status',
        'Recorded By',
        'Created Date',
        'Action'
    ];
} else {
    $tableHeaders = [
        'Sr No.',
        'Lead / Business',
        'Follow-up Type',
        'Follow-up Date & Time',
        'Lead Status',
        'Discussion Remarks',
        'Response',
        'Followup Status',
        'Recorded By',
        'Created Date',
        'Action'
    ];
}
include BASE_PATH . '/component/datatable.php';
?>

<!-- MODAL: Add / Edit Follow-up Modal -->
<div class="modal fade" id="followupFormModal" tabindex="-1" aria-labelledby="followupFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="calendar-clock" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-semibold mb-0" id="followupFormModalLabel">Schedule Follow-up</h5>
                        <small class="text-muted" id="followupLeadSub">-</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followupCrudForm">
                <input type="hidden" name="action" id="fuFormAction" value="add_followup">
                <input type="hidden" name="followup_id" id="fuFormFuId" value="0">
                <input type="hidden" name="lead_id" id="fuFormLeadId" value="<?= $leadId ?>">
                
                <div class="modal-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold">Type</label>
                            <select class="form-select form-select-sm" name="followup_type" id="modalFuType" required>
                                <?php foreach ($leadFollowupTypes as $fType): ?>
                                    <option value="<?= htmlspecialchars($fType['id']) ?>"><?= htmlspecialchars($fType['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold">Date & Reminder Time</label>
                            <input type="text" class="form-control form-control-sm" name="followup_date" id="modalFuDateTime" placeholder="Select Date & Time" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold">Lead Stage</label>
                            <select class="form-select form-select-sm" name="stage" id="modalFuStage">
                                <?php foreach ($leadStatuses as $stg): ?>
                                    <option value="<?= (int)$stg['id'] ?>"><?= htmlspecialchars($stg['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fs-12 fw-semibold">Discussion Remarks / Action Plan</label>
                        <textarea class="form-control form-control-sm" name="remarks" id="modalFuRemarks" rows="3" placeholder="e.g. Call to clarify pricing packages, user interested in 10-account tier..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSaveFuModal">
                        <span id="btnSaveFuText"><i data-lucide="check" class="fs-13 align-middle me-1"></i> Save Follow-up</span>
                        <span id="btnSaveFuLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Add Followup Response Modal -->
<div class="modal fade" id="followupResponseModal" tabindex="-1" aria-labelledby="followupResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                        <i data-lucide="message-square-check" class="fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-16 fw-semibold mb-0" id="followupResponseModalLabel">Add Followup Response</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followupResponseForm">
                <input type="hidden" name="action" value="submit_followup_response">
                <input type="hidden" name="followup_id" id="respFollowupId" value="0">
                <input type="hidden" name="lead_id" id="respLeadId" value="0">
                
                <div class="modal-body p-3">
                    <!-- Inquiry / Customer Info Header Cards -->
                    <div class="row g-2 mb-3 bg-light-subtle p-2 rounded border">
                        <div class="col-md-6">
                            <div class="fs-13 text-dark"><strong>Inquiry :-</strong> <span id="respInquiryNo" class="text-primary fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Customer Name :-</strong> <span id="respCustomerName" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Mobile No :-</strong> <a href="#" id="respMobileNo" class="text-primary text-decoration-none">-</a></div>
                        </div>
                        <div class="col-md-6">
                            <div class="fs-13 text-dark"><strong>Inquiry Date :-</strong> <span id="respInquiryDate" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Contact Person Name :-</strong> <span id="respContactPerson" class="fw-medium">-</span></div>
                            <div class="fs-13 text-dark mt-1"><strong>Whatsapp No :-</strong> <a href="#" id="respWhatsappNo" class="text-success text-decoration-none">-</a></div>
                        </div>
                    </div>

                    <!-- Response Input -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Response</label>
                        <textarea class="form-control" name="response" id="respResponseText" rows="3" placeholder="Response" required></textarea>
                    </div>

                    <!-- Follow-up Action Select -->
                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">Follow-up Action</label>
                        <select class="form-select" name="followup_action" id="respFollowupAction" required>
                            <option value="">Follow-up Action</option>
                            <option value="next-followup">next-follow up</option>
                            <option value="end-followup">end-followup</option>
                        </select>
                    </div>

                    <!-- Conditional Next Followup Fields (Shown when 'next-follow up' selected) -->
                    <div id="nextFollowupFields" class="d-none border rounded p-3 bg-light-subtle mb-2">
                        <h6 class="fs-13 fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                            <i data-lucide="calendar-plus" class="fs-14 text-primary"></i> Schedule Next Follow-up
                        </h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Next Follow-up Date & Time</label>
                                <input type="text" class="form-control form-control-sm" name="next_followup_date" id="respNextDateTime" placeholder="Select Date & Time">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-12 fw-semibold">Follow-up Type</label>
                                <select class="form-select form-select-sm" name="next_followup_type" id="respNextType">
                                    <option value="">-- Select Type --</option>
                                    <?php foreach ($leadFollowupTypes as $fType): ?>
                                        <option value="<?= htmlspecialchars($fType['id']) ?>"><?= htmlspecialchars($fType['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fs-12 fw-semibold">Discuss Topic / Next Plan</label>
                                <textarea class="form-control form-control-sm" name="next_discuss_topic" id="respNextDiscuss" rows="2" placeholder="e.g. Call back after price quotation review, discuss customized features..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSubmitRespModal">
                        <span id="btnSubmitRespText"><i data-lucide="check" class="fs-13 align-middle me-1"></i> Submit Response</span>
                        <span id="btnSubmitRespLoader" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include BASE_PATH . '/include/footer.php';
?>

<script>
$(document).ready(function() {
    let fpInstance = null;
    if (typeof flatpickr !== 'undefined') {
        fpInstance = flatpickr('#modalFuDateTime', {
            enableTime: true,
            dateFormat: "d-m-Y H:i",
            altInput: true,
            altFormat: "d-m-Y h:i K",
            time_24hr: false,
            minDate: "today"
        });
    }

    let nextFpInstance = null;
    if (typeof flatpickr !== 'undefined') {
        nextFpInstance = flatpickr('#respNextDateTime', {
            enableTime: true,
            dateFormat: "d-m-Y H:i",
            altInput: true,
            altFormat: "d-m-Y h:i K",
            time_24hr: false,
            minDate: "today"
        });
    }

    // 1. Open Modal for Adding New Follow-up
    $(document).on('click', '.btn-open-add-fu', function(e) {
        e.preventDefault();
        $('#fuFormAction').val('add_followup');
        $('#fuFormFuId').val('0');
        $('#followupFormModalLabel').text('Schedule Follow-up');
        $('#btnSaveFuText').html('<i data-lucide="check" class="fs-13 align-middle me-1"></i> Save Follow-up');

        let busName = $(this).data('name') || '';
        let stage = $(this).data('stage') || '';
        if (stage) $('#modalFuStage').val(stage);
        $('#modalFuRemarks').val('');

        if (fpInstance) fpInstance.clear();

        let modalObj = bootstrap.Modal.getOrCreateInstance(document.getElementById('followupFormModal'));
        modalObj.show();
        if (window.lucide) lucide.createIcons();
    });

    // 2. Open Modal for Editing an existing Pending Follow-up
    $(document).on('click', '.btn-edit-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: { action: 'get_followup', followup_id: fuId },
            dataType: "json",
            success: function(res) {
                if (res.status === true && res.data) {
                    let d = res.data;
                    $('#fuFormAction').val('update_followup');
                    $('#fuFormFuId').val(d.id);
                    $('#followupFormModalLabel').text('Edit Follow-up');
                    $('#btnSaveFuText').html('<i data-lucide="check" class="fs-13 align-middle me-1"></i> Update Follow-up');

                    if (d.followup_type) $('#modalFuType').val(d.followup_type);
                    if (d.stage) $('#modalFuStage').val(d.stage);
                    $('#modalFuRemarks').val(d.remarks || '');

                    if (fpInstance && d.formatted_date) {
                        fpInstance.setDate(d.formatted_date, true, "d-m-Y H:i");
                    }

                    let modalObj = bootstrap.Modal.getOrCreateInstance(document.getElementById('followupFormModal'));
                    modalObj.show();
                    if (window.lucide) lucide.createIcons();
                } else {
                    showToast(res.message || 'Error fetching record', 'error');
                }
            },
            error: function() {
                showToast('Server error while fetching follow-up.', 'error');
            }
        });
    });

    // 3. Save / Update Follow-up Submit Handler
    $('#followupCrudForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#btnSaveFuModal');
        let $text = $('#btnSaveFuText');
        let $loader = $('#btnSaveFuLoader');

        $btn.prop('disabled', true);
        $text.addClass('d-none');
        $loader.removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    let modalObj = bootstrap.Modal.getInstance(document.getElementById('followupFormModal'));
                    if (modalObj) modalObj.hide();

                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to save follow-up', 'error');
                }
            },
            error: function() {
                showToast('Server error while saving follow-up.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.removeClass('d-none');
                $loader.addClass('d-none');
                if (window.lucide) lucide.createIcons();
            }
        });
    });

    // 4. Open Followup Response Modal on Right Action Button click
    $(document).on('click', '.btn-open-response-modal', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let fuId = $btn.data('id');
        let leadId = $btn.data('lead-id') || 0;
        let inquiry = $btn.data('inquiry') || ('INQ-' + leadId);
        let inqDate = $btn.data('inquiry-date') || '-';
        let custName = $btn.data('cust-name') || '-';
        let contactPerson = $btn.data('contact-person') || '-';
        let mobile = String($btn.data('mobile') || '').trim();
        let whatsapp = String($btn.data('whatsapp') || mobile).trim();

        $('#respFollowupId').val(fuId);
        $('#respLeadId').val(leadId);
        $('#respInquiryNo').text(inquiry);
        $('#respInquiryDate').text(inqDate);
        $('#respCustomerName').text(custName);
        $('#respContactPerson').text(contactPerson);

        $('#respMobileNo').text(mobile || '-');
        if (mobile && mobile !== '-') {
            $('#respMobileNo').attr('href', 'tel:' + mobile);
        } else {
            $('#respMobileNo').removeAttr('href');
        }

        $('#respWhatsappNo').text(whatsapp || '-');
        if (whatsapp && whatsapp !== '-') {
            let cleanPhone = whatsapp.replace(/\D/g, '');
            if (cleanPhone) {
                $('#respWhatsappNo').attr('href', 'https://wa.me/' + cleanPhone);
            } else {
                $('#respWhatsappNo').removeAttr('href');
            }
        } else {
            $('#respWhatsappNo').removeAttr('href');
        }

        $('#respResponseText').val('');
        $('#respFollowupAction').val('');
        $('#nextFollowupFields').addClass('d-none');
        $('#respNextType').val('');
        $('#respNextDiscuss').val('');
        if (nextFpInstance) nextFpInstance.clear();

        let modalObj = bootstrap.Modal.getOrCreateInstance(document.getElementById('followupResponseModal'));
        modalObj.show();
        if (window.lucide) lucide.createIcons();
    });

    // 5. Follow-up Action Change: show/hide Next Follow-up fields
    $('#respFollowupAction').on('change', function() {
        let val = $(this).val();
        if (val === 'next-followup') {
            $('#nextFollowupFields').removeClass('d-none');
            $('#respNextDateTime').prop('required', true);
            $('#respNextType').prop('required', true);
        } else {
            $('#nextFollowupFields').addClass('d-none');
            $('#respNextDateTime').prop('required', false);
            $('#respNextType').prop('required', false);
        }
        if (window.lucide) lucide.createIcons();
    });

    // 6. Submit Followup Response
    $('#followupResponseForm').on('submit', function(e) {
        e.preventDefault();
        let $btn = $('#btnSubmitRespModal');
        let $text = $('#btnSubmitRespText');
        let $loader = $('#btnSubmitRespLoader');

        $btn.prop('disabled', true);
        $text.addClass('d-none');
        $loader.removeClass('d-none');

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    let modalObj = bootstrap.Modal.getInstance(document.getElementById('followupResponseModal'));
                    if (modalObj) modalObj.hide();

                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to submit response', 'error');
                }
            },
            error: function() {
                showToast('Server error while saving response.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $text.removeClass('d-none');
                $loader.addClass('d-none');
                if (window.lucide) lucide.createIcons();
            }
        });
    });

    // 7. Mark Follow-up Completed (backward compatible fallback)
    $(document).on('click', '.btn-mark-fu', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let fuId = $btn.data('id');
        let newStatus = $btn.data('status');

        $btn.prop('disabled', true);

        $.ajax({
            url: SITE_URL + "admin/setting/lead/ajax.php",
            type: "POST",
            data: { action: 'update_followup_status', followup_id: fuId, status: newStatus },
            dataType: "json",
            success: function(res) {
                if (res.status === true) {
                    showToast(res.message, 'success');
                    if (typeof refreshFollowupNotifications === 'function') {
                        refreshFollowupNotifications();
                    }
                    if ($.fn.DataTable && $('.data-table').length) {
                        $('.data-table').DataTable().ajax.reload(null, false);
                    }
                } else {
                    showToast(res.message || 'Failed to update status', 'error');
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                showToast('Server error while updating follow-up.', 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    // 8. Delete Follow-up Record
    $(document).on('click', '.btn-delete-fu', function(e) {
        e.preventDefault();
        let fuId = $(this).data('id');
        if (!fuId) return;

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this follow-up record?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: SITE_URL + "admin/setting/lead/ajax.php",
                    type: "POST",
                    data: { action: 'delete_followup', followup_id: fuId },
                    dataType: "json",
                    success: function(res) {
                        if (res.status === true) {
                            showToast(res.message, 'success');
                            if (typeof refreshFollowupNotifications === 'function') {
                                refreshFollowupNotifications();
                            }
                            if ($.fn.DataTable && $('.data-table').length) {
                                $('.data-table').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            showToast(res.message || 'Failed to delete follow-up', 'error');
                        }
                    },
                    error: function() {
                        showToast('Server error while deleting follow-up.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
