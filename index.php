<?php

$posts = get_posts([
	"numberposts" => -1,
]);

$issues = get_posts([
	"numberposts" => -1,
	'post_type' => 'parutions'
]);

foreach ($issues as $issue) {
	$issue_id = $issue->ID;
	$custom_fields = get_post_custom($issue_id);

	// ACF doesn't return formatted string through custom_fields
	// (no <p>). get_field() doesn't work. Using get_field_objects
	// instead.
	$intro_text = get_field_objects($issue_id)["series_text"]["value"];

	$issue_fields = [
		"title" => cleanArticleTitle($issue->post_title),
		"subtitle" => $custom_fields["series_subtitle"][0] ?? "",
		"number" => $custom_fields["series_number"][0],
		"intro_text" => cleanHTML($intro_text),
		"credits" => $custom_fields["credits"][0] ?? "",
		"date" => $issue->post_date,
		"references" => $custom_fields["bibliographie"][0] ?? "",
	];

	$raw_posts = get_posts([
		"numberposts" => -1,
		'post_type' => 'post',
		'meta_key' => 'series_number',
		'meta_value' => $issue_fields["number"],
	]);

	$slugified_title = slugify($issue_fields["title"]);
	$issue_slug = $issue_fields["number"] . "_" . $slugified_title;

	// Creates folders
	// mkdir("content/" . $issue_slug);

	$issue_file_content = "";

	foreach ($issue_fields as $field_key => $field_value) {
		$issue_file_content .= "$field_key: $field_value\n\n----\n\n";
	}

	file_put_contents("content/$issue_slug/issue.txt", $issue_file_content);

	foreach ($raw_posts as $raw_post) {
		$post_id = $raw_post->ID;
		$custom_fields = get_post_custom($post_id);

		// ACF doesn't return formatted string through custom_fields
		// (no <p>). get_field() doesn't work. Using get_field_objects
		// instead.
		$article_text = get_field_objects($post_id)["article_text"]["value"];
		$abstract_fr = get_field_objects($post_id)["abstract_fr"]["value"];
		$abstract_en = get_field_objects($post_id)["abstract_en"]["value"];

		$authors_name = $custom_fields["article_author"][0];
		$authors_affiliation = $custom_fields["article_affiliation"][0];
		$authors = "-\n  name: $authors_name\n  affiliation: $authors_affiliation";

		$post = [
			"title" => cleanArticleTitle($raw_post->post_title),
			"subtitle" => $custom_fields["article_subtitle"][0],
			"authors" => $authors,
			"abstract_fr" => cleanHTML($abstract_fr),
			"abstract_en" => cleanHTML($abstract_en),
			"text" => cleanHTML($article_text),
			"references" => cleanHTML($custom_fields["article_references"]),
			"number" => $custom_fields["article_number"][0] ?? 0,
		];

		$post_references_list = "";

		foreach ($post["references"] as $ref) {
			$post_references_list .= "\n- $ref";
		}

		$post["references"] = $post_references_list;

		$post_slug = slugify($post["title"]);
		$post_dir = $post["number"] . "_" . $post_slug;

		// mkdir("content/$issue_slug/$post_dir");

		// echo "<pre><code>$post_slug</code></pre>";

		$post_content_file = "";

		foreach ($post as $field_key => $field_value) {
			$post_content_file .= "$field_key: $field_value\n\n----\n\n";
		}

		file_put_contents("content/$issue_slug/$post_dir/article.txt", $post_content_file);
	}
}

function slugify($title)
{
	$title = trim(strtolower($title));
	$title = str_replace(
		["è", "é", " ", "'",  "’", "à", "œ", "/", "î", "ḍ", "ó", "ô", "É", "ú", "ê", "ï", "â", "ù", "û", "Á", ".", "?", ",", "(", ")", "«", "»", "<-i>", "<i>", "<p>", "<-p>", "<", ">", "“", "{", "}", "”", "=", "3", ":"],
		["e", "e", "-", "-", "-", "a", "oe", "-", "i", "d", "o", "o", "e", "u", "e", "i", "a", "u", "u", "a"],
		$title
	);
	$title = preg_replace([
		"/-{2,}/",
		"/\p{Zs}/u",
		"/-$/",
		"/\s/",
		"/\[[^\]]*\]/",
		"/^-/"
	], [
		"-"
	], $title);

	return $title;
}

function cleanArticleTitle($title)
{
	$title = preg_replace(
		[
			"/\[[^\]]+\] /",
			"/<\/?b>/",
			"/<\/?i>/",
			"/<\/?em>/"
		],
		"",
		$title
	);

	return $title;
}

function cleanHTML($html)
{
	$html = preg_replace(
		[
			"/\p{Zs}{2,}/u", // weird spaces
			"/<i>/",
			"/<\/i>/",
			"/\sclass\s*=\s*\"[^\"]*\"/i", // all class attr
			"/\sheight\s*=\s*\"[^\"]*\"/i", // height attr (img)
			"/\swidth\s*=\s*\"[^\"]*\"/i", // width attr (img)
			"/<\/?strong>/", // <strong> and </strong>
			"/<\/?b>/", // <b>
			"/<\/?sup>/", // <sup>
			"/<\/?div[^>]*>/i", // all divs
			"/<\/?span[^>]*>/i", // all spans
			"/&nbsp;/",
			"/\n{3,}/" // more than 2 new lines
		],
		[
			" ",
			"<em>",
			"</em>",
		],
		$html
	);

	return $html;
}
