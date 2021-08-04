<?php


interface ilApiInterface
{
    public function isValidAppointmentUser(): bool;

    public function isUserModerator(): bool;

    public function isUserAdmin(): bool;

    public function isModeratorPresent(): bool;

    public function isMeetingStartable(): bool;

    public function isMeetingRunning(): bool;

    public function hasSessionObject(): bool;

    #public function getRecordings(): array;

    #public function getUserAvatar(): string;


}