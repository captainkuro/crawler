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

                $item['image'] = $this->extract_images($h2);
                $content = $h2->find('.the_comic', 0)->find('.article-content', 0);
                $pagination = $content->find('.pagination', 0);
                if ($pagination) {
                    foreach ($pagination->find('a') as $a) {
                        if ($a->{'class'} == 'next') continue;
                        $aurl = $domain . $a->href;
                        $p3 = new Page($aurl);
                        $h3 = new simple_html_dom();
                        $h3->load($p3->content());
                        $item['image'] = array_merge($item['image'], $this->extract_images($h3));
                    }
                }
                $item['image'] = implode('<br>', $item['image']);


                $result[] = $item;
            }
        }
        return $result;
    }

    private function extract_images($html) {
        $images = array();
        $content = $html->find('.the_comic', 0)->find('.article-content', 0);
        foreach ($content->find('img') as $img) {
            $images[] = $img->outertext();
        }
        return $images;
    }
}