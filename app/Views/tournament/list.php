<div class="buttons d-flex justify-content-end">
    <?php if ($navActive == 'shared'): ?>
    <div class="buttons d-flex justify-content-end mb-3">
        <input type="radio" class="btn-check" name="share-type" id="shared-by" value="by" autocomplete="off" <?= ($shareType != 'wh') ? 'checked' : '' ?>>
        <label class="btn" for="shared-by">Shared by me</label>

        <input type="radio" class="btn-check" name="share-type" id="shared-with" value="wh" autocomplete="off" <?= ($shareType == 'wh') ? 'checked' : '' ?>>
        <label class="btn" for="shared-with">Shared with me</label>
    </div>
    <?php else: ?>
    <a class="btn btn-success" href="<?php echo base_url('/tournaments/create') ?>"><i class="fa-sharp fa-solid fa-plus"></i> Create</a>
    <?php endif ?>
</div>

<table class="table align-middle">
    <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Tournament Name</th>
            <th scope="col">Type</th>
            <th scope="col">Status</th>
            <th scope="col">Created Time</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $order = 1; ?>
        <?php foreach ($tournaments as $index => $tournament) : ?>
        <?php if (isset($tournament['status'])): ?>
        <tr data-id="<?= $tournament['id'] ?>">
            <th scope="row"><?= $order++ ?></th>
            <td>
                <a href="<?= base_url('tournaments/' . $tournament['id'] . '/view') ?>"><?= $tournament['name'] ?></a>
            </td>
            <td><?= ($tournament['type'] == 1) ? "Single" : "Double" ?></td>
            <td data-label="status"><?= TOURNAMENT_STATUS_LABELS[$tournament['status']] ?></td>
            <td><?= $tournament['created_at'] ?></td>
            <td>
                <div class="btn-groups list-group">
                    <button class="btn text-start collapse-actions-btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseActions-<?= $index ?>" aria-expanded="false" aria-controls="collapseActions-<?= $index ?>">
                        <i class="fa-solid fa-plus"></i> View Actions
                    </button>
                    <div class="collapse" id="collapseActions-<?= $index ?>">
                        <div class="card card-body p-3">
                            <a href="javascript:;" class="rename" data-id="<?= $tournament['id'] ?>">Rename</a>
                            <a href="javascript:;" class="reset" data-id="<?= $tournament['id'] ?>" data-name="<?= $tournament['name'] ?>" data-bs-toggle="modal" data-bs-target="#resetConfirm">Reset</a>
                            <a href="javascript:;" class="archive" data-id="<?= $tournament['id'] ?>" data-name="<?= $tournament['name'] ?>" data-bs-toggle="modal" data-bs-target="#archiveConfirm">Archive</a>
                            <a href="javascript:;" class="restore" data-id="<?= $tournament['id'] ?>" data-name="<?= $tournament['name'] ?>" data-bs-toggle="modal" data-bs-target="#restoreConfirm">Restore</a>
                            <a href="javascript:;" class="delete" data-id="<?= $tournament['id'] ?>" data-name="<?= $tournament['name'] ?>" data-bs-toggle="modal" data-bs-target="#deleteConfirm">Delete</a>
                            <a href="javascript:;" class="change-status" data-id="<?= $tournament['id'] ?>" data-status="<?= $tournament['status'] ?>">Change Status</a>
                            <a href="javascript:;" class="music-setting-link" data-id="<?= $tournament['id'] ?>">Music Settings</a>
                            <a href="javascript:;" class="share" data-id="<?= $tournament['id'] ?>" data-name="<?= $tournament['name'] ?>" data-bs-toggle="modal" data-bs-target="#shareModal">Share</a>
                            <a href="javascript:;" class="view-log" data-id="<?= $tournament['id'] ?>" data-name="<?= $tournament['name'] ?>" data-bs-toggle="modal" data-bs-target="#viewLogModal">View Log</a>
                        </div>
                    </div>
                </div>
                <a href="javascript:;" class="save visually-hidden" data-id="<?= $tournament['id'] ?>" data-status="<?= $tournament['status'] ?>" onClick="saveChange(event)">Save</a>
            </td>
        </tr>
        <?php endif ?>
        <?php endforeach; ?>
    </tbody>
</table>