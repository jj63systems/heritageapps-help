<?php

namespace HeritageApps\Help\Contracts;

/**
 * Apps implement this interface to provide app-specific context to the AI assistant.
 *
 * Bind your implementation in your app's service provider:
 *
 *   $this->app->bind(AppContextInterface::class, MyAppContextProvider::class);
 */
interface AppContextInterface
{
    /**
     * Return a string describing the app's current data inventory
     * (models, entity counts, configuration, etc.).
     * This is injected into the AI context message on each request.
     */
    public function getInventory(): string;

    /**
     * Return live configuration/statistics from the database.
     * This is injected into the AI context message on each request.
     */
    public function getLiveConfiguration(): string;

    /**
     * Return whether the given user is allowed to use the AI assistant.
     */
    public function canUseAi(\Illuminate\Contracts\Auth\Authenticatable $user): bool;

    /**
     * Return whether the given user is allowed to view help articles.
     */
    public function canViewHelp(\Illuminate\Contracts\Auth\Authenticatable $user): bool;

    /**
     * Return whether the given user is allowed to edit help articles in the library.
     */
    public function canEditArticles(\Illuminate\Contracts\Auth\Authenticatable $user): bool;

    /**
     * Filter audience when loading articles (e.g. based on user role).
     * Return the audience value(s) visible to the given user: 'admin', 'user', 'both'.
     *
     * @return array<string>
     */
    public function visibleAudienceFor(\Illuminate\Contracts\Auth\Authenticatable $user): array;
}
