<?php
include 'include/header.php';
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card">

            <div class="card-header">
                <h6 class="mb-0">Followups</h6>
            </div>

            <div class="card-body">
                <div class="table-responsive">

                    <table class="table table-hover align-middle table-striped mb-0 data-table" data-ajaxurl="ajax/followups.php">

                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Company ID</th>
                                <th>Inquiry ID</th>
                                <th>Date & Time</th>
                                <th>Details</th>
                                <th>Through</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        </tbody>

                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

<?php
include 'include/footer.php';
?>

</body>

</html>