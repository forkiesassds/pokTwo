<?php
namespace pokTwo;
class Videos
{
	// this is like that so that it stays readable in the code and doesn't introduce a fucking huge horizontal scrollbar on github. -grkb 3/31/2022
	//why the hell is it using tutorial names?
	public static $recommendedfields = "
		jaccard.video_id,
		jaccard.intersect,
		jaccard.union,
		jaccard.intersect / jaccard.union AS 'jaccard index'
	FROM
		(
		SELECT
			c2.video_id AS video_id,
			COUNT(ct2.tag_id) AS 'intersect',
			(
			SELECT
				COUNT(DISTINCT ct3.tag_id)
			FROM
				tag_index ct3
			WHERE
				ct3.video_id IN(c1.id, c2.id)
		) AS 'union'
	FROM
		videos AS c1
	INNER JOIN videos AS c2
	ON
		c1.id != c2.id
	LEFT JOIN tag_index AS ct1
	ON
		ct1.video_id = c1.id
	LEFT JOIN tag_index AS ct2
	ON
		ct2.video_id = c2.id AND ct1.tag_id = ct2.tag_id
	WHERE
		c1.id = ?
	GROUP BY
		c1.id,
		c2.id
	) AS jaccard
	ORDER BY
		jaccard.intersect / jaccard.union
	DESC";

	static function videofields()
	{
		//todo: make this cleaner.
		return 'v.id, v.video_id, v.title, v.description, v.time, (SELECT COUNT(*) FROM views WHERE video_id = v.video_id) AS views, (SELECT COUNT(*) FROM comments WHERE id = v.video_id) AS comments, (SELECT COUNT(*) FROM favorites WHERE video_id = v.video_id) AS favorites, (SELECT COUNT(*) FROM favorites WHERE video_id = v.video_id) AS favorites, v.videolength, v.category_id, v.author';
	}

	/**
	 * Return the interger ID of a video.
	 *
	 * @param string $video The randomized video ID.
	 * @return int the ID of a video.
	 */
	static function getVideoIntID($video)
	{
		global $sql;
		return $sql->result("SELECT id FROM videos WHERE video_id = ?", [$video]);
	}

	/**
	 * Return a list of videos, Limit and order is required.
	 *
	 * @param string $orderBy A column in the videos table.
	 * @param int $limit The limit.
	 * @param string $whereSomething Precise what column.
	 * @param string $whereEquals Precise the value of the column.
	 * @return array A video list, ordered by what $orderBy specified.
	 */
	static function getVideos($orderBy, $limit, $whereSomething = null, $whereEquals = null)
	{
		global $userfields, $videofields, $sql;
		if (isset($whereSomething))
		{
			$videoList = $sql->fetchArray($sql->query("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? ORDER BY $orderBy LIMIT $limit", [$whereEquals]));
		}
		else
		{
			$videoList = $sql->fetchArray($sql->query("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id ORDER BY $orderBy LIMIT $limit"));
		}
		foreach ($videoList as &$video) {
			$video['tags'] = Tags::getVideoTags($video['id']);
		}
		return $videoList;
	}
	

	/**
	 * Return a list of videos in an alternative way.
	 *
	 * @param string $whereSomething Precise what column.
	 * @param string $whereEquals Precise the value of the column.
	 * @return array A video list, ordered by what $orderBy specified.
	 */
	static function fetchVideos($whereSomething, $whereEquals, $orderBy = null, $limit = null)
	{
		global $userfields, $videofields, $sql;
		if (isset($orderBy, $limit))
		{
			$videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? ORDER BY $orderBy LIMIT $limit", [$whereEquals]);
		}
		elseif (isset($orderBy))
		{
			$videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? ORDER BY $orderBy", [$whereEquals]);
		}
		elseif (isset($limit))
		{
			$videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ? LIMIT $limit", [$whereEquals]);
		}
		else
		{
			$videoList = $sql->fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE $whereSomething = ?", [$whereEquals]);
		}
		return $videoList;
	}

	/**
	 * Return a list of videos that are simillar to the video the user is watching.
	 *
	 * @param string $videoID The ID of the currently watched video.
	 * @return array A video list, ordered by "relevancy".
	 */

	static function getRecommended($videoID)
	{
		global $userfields, $videofields, $recommendedfields, $sql;
		$recommendfields = self::$recommendedfields;
		$intID = self::getVideoIntID($videoID);
		$recommendedList = $sql->fetchArray($sql->query("SELECT $recommendfields LIMIT 20", [$intID]));
		$videoList = array();
		foreach ($recommendedList as $row)
		{
			//$videoData = fetch("SELECT $userfields $videofields FROM videos v JOIN users u ON v.author = u.id WHERE v.video_id = ?", [$row['video_id']]);
			$videoData = self::fetchVideos("v.video_id", $row['video_id']);
			array_push($videoList, $videoData);
		}
		return $videoList;
	}

	/**
	 * Return a list of videos that are simillar to the video the user is watching.
	 *
	 * @param string $videoID The ID of the currently watched video.
	 * @return array Number of every single recommended video, goes further than 20 if there are more than 20 recommended videos.
	 */
	static function countRecommended($videoID)
	{
		global $userfields, $videofields, $recommendedfields, $sql;
		$recommendfields = self::$recommendedfields; //the fuck? -grkb 4/4/2022
		$intID = self::getVideoIntID($videoID);
		$recommendedList = $sql->fetch("SELECT COUNT(jaccard.video_id), $recommendfields", [$intID]) ['COUNT(jaccard.video_id)']; // FIXME: don't do the ordering shit, also does it count all uploaded videos or just the relevant ones -grkb 3/31/2022.
		return $recommendedList;
	}

	/**
	 * Return the link to the FLV version of the video.
	 *
	 * @param string $videoID The ID of the currently watched video.
	 * @return string A link to the FLV version of the video, or if nothing is inputted, an error.
	 */
	static function getFlashVideo($videoID)
	{
		if (isset($videoID) ? $videoID : null) {
		$file = "/media/" . $videoID . ".flv";
		return $file;
		} else {
		die("getFlashVideo Error: videoID is missing!");
		}
	}
}