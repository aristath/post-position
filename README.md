# post-position

A simple plugin that allows users to define a custom position for a post inside the main WP query.
Adds a metabox in posts where users can enter a numeric value (1 to infinite). The post will then NOT show up in the predefined order in the query but instead will be injected at the user-defined position.

## Available filters:

* `post_position_meta_key` (string) the meta-key that will be used.
* `post_position_avoid_doubles` (bool) Plucks existing posts from the query before re-inserting them. If this is set to false you may encounter double entries.
* `post_position_supported_post_types` (array) An array of supported post-types.
* `post_position_conditions` (array) An array of function-names that will serve as conditions to determine if the query should be altered or not.
* `post_position_posts_per_page` (int) How many posts to include in the custom query that will get custom-ordered posts. Defaults the the `posts_per_page` WP setting.
