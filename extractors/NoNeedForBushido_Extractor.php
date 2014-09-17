<?php
// http://nn4b.com/?webcomic1=480
class NoNeedForBushido_extractor implements Extractor {
	public function can_extract($url) {
		return strpos($url, 'http://nn4b.com/') === 0;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();
		$pattern_url = 'http://nn4b.com/?webcomic1=%s';
		for ($i=$s; $i<=$n; $i++) {
			$purl = sprintf($pattern_url, $i);
			$p = new Page($purl);
			$p->go_line('"og:image"');
			$src = $p->curr_line()->cut_between('content="', '"')->to_s();

			$item = array(
				'image' => "<img src='$src'>",
				'link' => "<a href='$purl'>Link</a>",
			);
			$result[] = $item;
		}
		return $result;
	}
}