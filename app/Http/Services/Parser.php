<?php


namespace App\Http\Services;


use App\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;

class Parser
{
    /**
     * @var string
     */
    private $siteUrl;

    /**
     * @var array
     */
    private $sitemaps = [];

    /**
     * @var \App\Parser
     */
    private $parser;

    /**
     * Parser constructor.
     *
     * @param string $siteUrl
     */
    public function __construct(string $siteUrl)
    {
        if (!isset(parse_url($siteUrl)['scheme'])) {
            $siteUrl = "https://$siteUrl";
        }

        $this->siteUrl = $siteUrl;
        $this->parser = \App\Parser::where('site', $siteUrl)->first();
        if (!$this->parser) {
            $this->parser = \App\Parser::create([
                'site' => $siteUrl,
                'last_parse' => Carbon::now()->subMonth()
            ]);
        }
        $this->parseRobots();
    }

    /**
     * Start parse
     */
    public function parse()
    {
        foreach ($this->sitemaps as $sitemap) {
            foreach ($this->parseUrl($sitemap) as $item) {
                $this->parseNewsContent($item);
            }
        }
        $this->parser->last_parse = Carbon::now();
        $this->parser->save();
    }

    /**
     * @param array $news
     */
    private function parseNewsContent(array $news)
    {
        $readability = new Readability(new Configuration());
        try {
            $readability->parse(Http::get($news['url'])->body());
            $news['content'] = $readability->getContent();
            Post::create($news);
        } catch (\Exception $e) {
            logger(sprintf('Error processing text: %s', $e->getMessage()));
        }
    }

    /**
     * @param string $sitemap
     * @return \Generator
     */
    private function parseUrl(string $sitemap)
    {
        $response = new \SimpleXMLElement(Http::get($sitemap)->body());
        if ($response->sitemap) {
            foreach ($response->sitemap as $item) {
                if ($this->parser->last_parse->diffInSeconds(Carbon::parse((string)$item->lastmod)) > 0 && preg_match("/(.)*sitemap([a-z_-])+.xml$/i", (string)$item->loc)) {
                    yield from $this->parseUrl((string)$item->loc);
                }
            }
        } else if ($response->url) {
            foreach ($response->url as $element) {
                if ($news = $element->children('news', true)) {
                    $newsDate = Carbon::parse((string)$news->news->publication_date);
                    if ($newsDate->diffInMonths(Carbon::now()) < 1 && $newsDate > $this->parser->last_parse) {
                        yield [
                            'url' => (string)$element->loc,
                            'title' => (string)$news->news->title,
                            'date' => (string)$newsDate
                        ];
                    }
                }
            }
        }
    }

    /**
     * Parse robots.txt
     */
    private function parseRobots()
    {
        $robotsUrl = "{$this->siteUrl}/robots.txt";
        $context = stream_context_create(
            array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                )
            )
        );
        $fh = fopen($robotsUrl,'r', null, $context);
        while (($line = fgets($fh)) != false) {
            if (preg_match("/^sitemap.*/i", $line)){
                $this->sitemaps[] = trim(explode(':', $line, 2)[1]);
            }
        }
    }
}
