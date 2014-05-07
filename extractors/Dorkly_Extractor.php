<?php

class Dorkly_Extractor implements Extractor {
    
    public function can_extract($url) {
        return strpos($url, 'http://www.dorkly.com/comics') === 0;
    }

    public function extract($columns, $s, $n, $url) {
        $result = array();
        $domain = 'http://www.dorkly.com';
        for ($i=$s; $i<=$n; $i++) {
            $purl = rtrim($url, '/') . '/page:'.$i;
            $p = new Page($purl);
            $h = new simple_html_dom();
            $h->load($p->content());

            foreach ($h->find('.browse_article') as $post) {
                $item = array();

                $title_a = $post->find('h3', 0)->find('a', 0);
                $item['title'] = $title_a->innertext;
                $item['url'] = $domain . $title_a->href;
                $item['link'] = $domain . $title_a->outertext();

                $p2 = new Page($item['url']);
                $h2 = new simple_html_dom();
                $h2->load($p2->content());

                $img = $h2->find('.the_comic', 0)->find('img', 0);
                $item['image'] = $img->outertext();

                $result[] = $item;
            }
        }
        return $result;
    }
}