<?php

namespace App\Services;

use App\Models\NotificationsModel;

class NotificationService
{
    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationsModel();
    }

    /**
     * Add a new notification.
     *
     * @param array $data Notification data
     * @return bool|int The ID of the inserted notification or false on failure
     */
    public function addNotification(array $data)
    {
        if ($this->notificationModel->insert($data)) {
            return $this->notificationModel->getInsertID();
        }
        return false;
    }

    /**
     * Get notifications for a specific user.
     *
     * @param int $userId User ID
     * @return array List of notifications
     */
    public function getNotifications(int $userId)
    {
        return $this->notificationModel->where('user_to', $userId)->where('mark_as_read', 0)->findAll();
    }

    /**
     * Mark a notification as read.
     *
     * @param int $notificationId Notification ID
     * @return bool True on success, false on failure
     */
    public function markAsRead(int $notificationId)
    {
        return $this->notificationModel->update($notificationId, ['mark_as_read' => 1]);
    }

    /**
     * Delete a notification.
     *
     * @param int $notificationId Notification ID
     * @return bool True on success, false on failure
     */
    public function deleteNotification(int $notificationId)
    {
        return $this->notificationModel->delete($notificationId);
    }
}