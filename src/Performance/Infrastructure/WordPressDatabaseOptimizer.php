<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Infrastructure;

use BackTo\Framework\Performance\Contracts\DatabaseOptimizerInterface;

/**
 * WordPress adapter for database cleanup and optimization.
 *
 * Performs safe cleanup operations:
 * - Post revisions (with configurable limit)
 * - Auto-drafts
 * - Trashed posts and comments
 * - Expired transients
 * - Spam comments
 * - Orphaned post/comment/user metadata
 */
class WordPressDatabaseOptimizer implements DatabaseOptimizerInterface
{
    private int $revisionsLimit;

    public function __construct(int $revisionsLimit = 5)
    {
        $this->revisionsLimit = $revisionsLimit;
    }

    public function cleanup(): array
    {
        global $wpdb;

        $results = [];

        $results['revisions'] = $this->deleteExcessRevisions($wpdb);
        $results['auto_drafts'] = $this->deleteAutoDrafts($wpdb);
        $results['trashed_posts'] = $this->deleteTrashedPosts($wpdb);
        $results['spam_comments'] = $this->deleteSpamComments($wpdb);
        $results['trashed_comments'] = $this->deleteTrashedComments($wpdb);
        $results['expired_transients'] = $this->deleteExpiredTransients($wpdb);
        $results['orphaned_postmeta'] = $this->deleteOrphanedPostMeta($wpdb);
        $results['orphaned_commentmeta'] = $this->deleteOrphanedCommentMeta($wpdb);

        return $results;
    }

    public function optimizeTables(): int
    {
        global $wpdb;

        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        $count = 0;

        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE `{$table}`");
            $count++;
        }

        return $count;
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteExcessRevisions($wpdb): int
    {
        if ($this->revisionsLimit <= 0) {
            return 0;
        }

        $revisionIds = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT r.ID FROM {$wpdb->posts} r
                INNER JOIN (
                    SELECT post_parent, ID FROM {$wpdb->posts}
                    WHERE post_type = 'revision'
                    ORDER BY post_date DESC
                ) ranked ON r.ID = ranked.ID
                WHERE r.post_type = 'revision'
                AND r.ID NOT IN (
                    SELECT sub.ID FROM (
                        SELECT ID, post_parent,
                        ROW_NUMBER() OVER (PARTITION BY post_parent ORDER BY post_date DESC) AS rn
                        FROM {$wpdb->posts}
                        WHERE post_type = 'revision'
                    ) sub WHERE sub.rn <= %d
                )",
                $this->revisionsLimit
            )
        );

        if (empty($revisionIds)) {
            return 0;
        }

        $ids = implode(',', array_map('intval', $revisionIds));
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ({$ids})");

        return count($revisionIds);
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteAutoDrafts($wpdb): int
    {
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
        );
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteTrashedPosts($wpdb): int
    {
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteSpamComments($wpdb): int
    {
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
        );
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteTrashedComments($wpdb): int
    {
        return (int) $wpdb->query(
            "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
        );
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteExpiredTransients($wpdb): int
    {
        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a
                INNER JOIN {$wpdb->options} b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                WHERE a.option_name LIKE %s
                AND b.option_value < %d",
                $wpdb->esc_like('_transient_') . '%',
                time()
            )
        );
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteOrphanedPostMeta($wpdb): int
    {
        return (int) $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.ID IS NULL"
        );
    }

    /**
     * @param \wpdb $wpdb
     */
    private function deleteOrphanedCommentMeta($wpdb): int
    {
        return (int) $wpdb->query(
            "DELETE cm FROM {$wpdb->commentmeta} cm
            LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
            WHERE c.comment_ID IS NULL"
        );
    }
}
