<?php

namespace App\Service;

use App\Entity\News;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ScrapingService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Scrape les 3 dernières offres d'emploi de keejob.com
     *
     * @return News[]
     */
    public function scrapeKeejob(): array
    {
        $url = 'https://www.keejob.com/offres-emploi/?keywords=stage';
        
        try {
            // Fetch the HTML content
            $response = $this->httpClient->request('GET', $url);
            $content = $response->getContent();
            
            // Create a crawler instance
            $crawler = new Crawler($content);
            
            // Extract job offers
            $offers = [];
            $count = 0;
            
            // Adjust the selector based on keejob.com structure
            $crawler->filter('article, .job-item, [data-testid="jobCard"], .offer-card')->each(function (Crawler $node) use (&$offers, &$count) {
                if ($count >= 3) {
                    return;
                }
                
                try {
                    $title = $node->filter('h2, h3, .job-title, [class*="title"]')->first()->text();
                    $link = $node->filter('a')->first()->attr('href');
                    $description = $node->filter('p, .job-description, [class*="description"]')->first()->text();
                    
                    // Make sure we have at least a title and link
                    if (!empty($title) && !empty($link)) {
                        // Ensure link is absolute URL
                        if (!str_starts_with($link, 'http')) {
                            $link = 'https://www.keejob.com' . $link;
                        }
                        
                        $news = new News();
                        $news->setTitle(substr($title, 0, 255)); // Ensure title fits in DB column
                        $news->setContent($description ?: 'Offre d\'emploi sur Keejob');
                        $news->setSourceLink($link);
                        $news->setIsExternal(true);
                        $news->setAuthorType('AI');
                        $news->setCreatedAt(new \DateTimeImmutable());
                        
                        $offers[] = $news;
                        $count++;
                    }
                } catch (\Exception $e) {
                    // Skip this offer if parsing fails
                    return;
                }
            });
            
            return $offers;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to scrape Keejob offers: ' . $e->getMessage());
        }
    }
}
