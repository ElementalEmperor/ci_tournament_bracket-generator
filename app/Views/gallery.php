<?= $this->extend('\App\Views\layout') ?>

<?= $this->section('title') ?>Tournament Gallery<?= $this->endSection() ?>

<?= $this->section('pageStyles') ?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.css">
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
<script type="text/javascript">
var tournamentsTable = null;
var datatableRows;

tournamentsTable = $('#tournamentGalleryTable').DataTable({
    "searching": true,
    "processing": true,
    "ajax": {
        "url": apiURL + '/tournaments/get-gallery' + window.location.search,
        "type": "POST",
        "dataSrc": "",
        "data": function(d) {
            d.user_id = <?= (auth()->user()) ? auth()->user()->id : 0 ?>; // Include the user_id parameter
            d.search_tournament = $('#tournamentSearchInputBox').val();
        }
    },
    "order": [
        [0, "asc"]
    ], // Initial sorting by the first column ascending
    "paging": true, // Enable pagination
    scrollX: true,
    "columnDefs": [{
        "orderable": false,
        "targets": [2, 3, 7, 8]
    }],
    // Add custom initComplete to initialize select all checkbox
    "initComplete": function(settings, json) {
        datatableRows = tournamentsTable.rows({
            'search': 'applied'
        }).nodes();

        var nameColumns = $('td[data-label="name"] span', datatableRows)
        var names = []
        nameColumns.each((i, element) => {
            if (!names.includes(element.textContent.trim())) {
                var option = $(`<option value="${element.textContent.trim()}">${element.textContent}</option>`)
                $('#userByFilter').append(option)

                names.push(element.textContent.trim())
            }
        })
    },
    "columns": [{
            "data": null,
            "render": function(data, type, row, meta) {
                return meta.row + 1; // Display index number
            }
        },
        {
            "data": "name",
            "render": function(data, type, row, meta) {
                return `<a href="${window.location.pathname}/${row.id}/view">${row.name}</a>`
            },
            "createdCell": function(td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'name');
            }
        },
        {
            "data": "type",
            "render": function(data, type, row, meta) {
                var type = 'Single'
                if (row.type == <?= TOURNAMENT_TYPE_DOUBLE ?>) {
                    type = "Double"
                }

                return type;
            }
        },
        {
            "data": "status",
            "render": function(data, type, row, meta) {
                var status = 'In progress'
                if (row.status == <?= TOURNAMENT_STATUS_COMPLETED ?>) {
                    status = 'Completed'
                }

                if (row.status == <?= TOURNAMENT_STATUS_ABANDONED ?>) {
                    status = 'Abandoned'
                }

                return status;
            },
            "createdCell": function(td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'status');
            }
        },
        {
            "data": "participants_count"
        },
        {
            "data": "available_start"
        },
        {
            "data": "available_end"
        },
        {
            "data": "public_url",
            "render": function(data, type, row, meta) {
                return `
                    <div class="col-auto input-group">
                        <input type="text" class="form-control" id="tournamentURL_${row.id}" value="${row.public_url}" aria-label="Tournament URL" aria-describedby="urlCopy" readonly="">
                        <button class="btn btn-outline-secondary input-group-text btnCopy" data-copyid="tournamentURL_${row.id}" type="button" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="Link Copied!">Copy</button>
                    </div>
                    `
            }
        },
        {
            "data": null,
            "render": function(data, type, row, meta) {
                let html = `<a href="javascript:;" class="archive" data-id="${row.id}" data-name="${row.name}" data-bs-toggle="modal" data-bs-target="#archiveConfirmModal">Archive</a>`
                if (row.archive == 1)
                    html = `<a href="javascript:;" class="restore" data-id="${row.id}" data-name="${row.name}" data-bs-toggle="modal" data-bs-target="#restoreConfirmModal">Restore</a>`
                return `<span data-toggle="tooltip" data-placement="top" title="${row.email}">${row.username}</span>`;
            },
            "createdCell": function(td, cellData, rowData, row, col) {
                $(td).attr('data-label', 'name');
            }
        },
        {
            "data": "created_at"
        },
    ],
    "createdRow": function(row, data, dataIndex) {
        // Add a custom attribute to the row
        $(row).attr('data-id', data.id); // Adds a data-id attribute with the row's ID
    }
});

$('#typeFilter').on('change', function() {
    var selectedType = $(this).val().toLowerCase();
    tournamentsTable.columns(2).search(selectedType).draw();
});

$('#stautsFilter').on('change', function() {
    var selectedStatus = $(this).val().toLowerCase();
    tournamentsTable.columns(3).search(selectedStatus).draw();
});

$('#userByFilter').on('change', function() {
    var selectedUser = $(this).val().toLowerCase().trim();
    tournamentsTable.columns(8).search(selectedUser).draw();
});

$(document).on('click', '.btnCopy', function(e) {
    var copyId = $(this).data("copyid");
    copyClipboard(copyId);
});

function copyClipboard(url_id) {
    // Get the text field
    var copyText = document.getElementById(url_id);

    // Select the text field
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text inside the text field
    if (navigator.clipboard) {
        navigator.clipboard.writeText(copyText.value);
    } else {
        document.execCommand('copy');
    }
}

function handleKeyPress(event) {
    tournamentsTable.ajax.reload()
}
</script>
<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="card shadow-sm">
    <div class="card- p-3">
        <div class="text-center">
            <h3>Welcome to the Tournament Gallery!</h3>
            <div class="gallery-description d-flex  flex-column justify-content-center">
                <p>Here, you can dive into the excitement of live tournaments. Whether you're signed in or just visiting, explore and spectate the action in real-time.</p>
                <p>Ready to watch some thrilling matches? Step right in, enjoy watching the competition unfold, and cheer on your favorite participants!</p>
            </div>
        </div>
        <div class="container justify-content-center mb-3">
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="tournamentSearchInputBox" value="<?= $searchString ?>" placeholder="Search for a tournament name or find out which tournaments a participant is competing in" onkeyup="handleKeyPress(event)">
                <button class="btn btn-primary" onclick="fetchDataAndUpdateTable()"><i class="fa fa-search"></i> Search</button>
            </div>
        </div>
        <div class="buttons d-flex justify-content-end">
            <a href="<?= base_url('tournaments/create') ?>" class="btn btn-success ms-2"><i class="fa-sharp fa-solid fa-plus"></i> Create</a>
            <a href="<?= base_url('gallery/export?filter=all') ?>" class="btn btn-success ms-2"><i class="fa-solid fa-file-csv"></i> Export</a>
        </div>
        <div class="table-responsive">
            <table id="tournamentGalleryTable" class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">#<br />&nbsp;</th>
                        <th scope="col">Tournament Name<br />&nbsp;</th>
                        <th scope="col">
                            <label for="typeFilter">Type:</label>
                            <select id="typeFilter" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <option value="Single">Single</option>
                                <option value="Double">Double</option>
                            </select>
                        </th>
                        <th scope="col">
                            <label for="statusFilter">Status:</label>
                            <select id="stautsFilter" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="In progress">In progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Abandoned">Abandoned</option>
                            </select>
                        </th>
                        <th scope="col"># Participants<br />&nbsp;</th>
                        <th scope="col">Availability Start<br />&nbsp;</th>
                        <th scope="col">Availability End<br />&nbsp;</th>
                        <th scope="col">Public URL<br />&nbsp;</th>
                        <th scope="col">
                            <label for="userByFilter">Created By:</label>
                            <select id="userByFilter" class="form-select form-select-sm">
                                <option value="">All Users</option>
                            </select>
                        </th>
                        <th scope="col">Created Time<br />&nbsp;</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>