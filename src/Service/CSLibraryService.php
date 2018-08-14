<?php

namespace BTJ\Scrapper\Service;

use \DateTime;
use BTJ\Scrapper\Container\EventContainerInterface;
use BTJ\Scrapper\Container\LibraryContainerInterface;
use BTJ\Scrapper\Container\NewsContainerInterface;

/**
 * Class CSLibraryService.
 *
 * @package BTJ\Scrapper\Service
 */
class CSLibraryService extends ScrapperService {

  public function getEventsLinks($url) : array {
    $links = [];
    $year = date('Y');
    for ($i = 1; $i <= 12; $i++) {
      $month = date('m', strtotime("$i/1/$year"));
      $first = "$year-$month-01";

      $date = new DateTime($first);
      $date->modify('last day of this month');
      $last = $date->format('Y-m-d');

      $crawler = $this->getTransport()->request('GET', $url . "/calendar/html?fDateMin=$first&fDateMax=$last");
      $month_links = $crawler->filter('a.page-link')->each(function ($node) {
        return $node->attr('href');
      });
      $links = array_merge($links, $month_links);
    }

    return array_unique(array_filter($links));
  }

  public function getNewsLinks($url) : array {
    $crawler = $this->getTransport()->request('GET', $url . '/search/content/html?fType=news&mode=full&no-cache-lang=sv');
    $links = $crawler->filter('a.page-link')->each(function ($node) {
      return $node->attr('href');
    });

    return array_filter($links);
  }

  public function getLibrariesLinks($url) : array {
    $crawler = $this->getTransport()->request('GET', $url . '/opening-hours?culture=sv');
    $links = $crawler->filter('a.library-name')->each(function ($node) {
      return $node->attr('href');
    });

    return array_filter($links);
  }

  /**
   * Scrap a CS Library CMS event page.
   *
   * @param string $url
   *   Event page url.
   * @param \BTJ\Scrapper\Container\EventContainerInterface $container
   *   Scrapping container object.
   */
  public function eventScrap(string $url, EventContainerInterface $container) : void {
    $crawler = $this->getTransport()->request('GET', $url);

    $crawler->filter('h1.page-title')->each(function ($node) use ($container) {
      $container->setTitle($node->text());
    });

    $crawler->filter('.page-introduction')->each(function ($node) use ($container) {
      $container->setBody($node->text());
    });

    $crawler->filter('.editorial-image > img')->each(function ($node) use ($container) {
      $container->setListImage($node->attr('src'));
    });

    $crawler->filter('.editorial-image > .content-type')->each(function ($node) use ($container) {
      $container->setCategory($node->text());
    });

    $crawler->filter('.event-date')->each(function ($node) use ($container) {
      $container->setDate($node->text());
    });

    $crawler->filter('.event-month')->each(function ($node) use ($container) {
      $container->setMonth($node->text());
    });

    $crawler->filter('.event-when > .value')->each(function ($node) use ($container) {
      $container->setTime($node->text());
    });

    $crawler->filter('.library-name > .page-link')->each(function ($node) use ($container) {
      $container->setLibrary($node->text());
    });

    $crawler->filter('.event-cost > .value')->each(function ($node) use ($container) {
      $container->setPrice((int) $node->text());
    });

    $targets = $crawler->filter('li.audience-value')->each(function ($node) {
      return trim(preg_replace('/\s+/', ' ', $node->text()));
    });
    $container->setTarget($targets);

    $tags = $crawler->filter('.tags li')->each(function ($node) {
      return trim(preg_replace('/\s+/', ' ', $node->text()));
    });
    $container->setTags($tags);
  }

  /**
   * Scrap a CS Library CMS news page.
   *
   * @param string $url
   *   News page url.
   * @param \BTJ\Scrapper\Container\NewsContainerInterface $container
   *   News container object.
   */
  public function newsScrap(string $url, NewsContainerInterface $container) : void {
    $crawler = $this->getTransport()->request('GET', $url);

    $crawler->filter('h1.page-title')->each(function ($node) use ($container) {
      $container->setTitle($node->text());
    });

    $crawler->filter('.page-introduction')->each(function ($node) use ($container) {
      $container->setLead($node->text());
    });

    $crawler->filter('.content-image > img')->each(function ($node) use ($container) {
      $container->setListImage($node->attr('src'));
    });

    $crawler->filter('.image-widget > img')->each(function ($node) use ($container) {
      $container->setTitleImage($node->attr('src'));
    });

    $crawler->filter('.template .zone-1')->each(function ($node) use ($container) {
      $container->setBody($node->html());
    });

    $targets = $crawler->filter('.audiences a')->each(function ($node) {
      return trim(preg_replace('/\s+/', ' ', $node->text()));
    });
    $container->setTarget($targets);

    $tags = $crawler->filter('.tags li')->each(function ($node) {
      return trim(preg_replace('/\s+/', ' ', $node->text()));
    });
    $container->setTags($tags);
  }

  /**
   * Scrap a CS Library CMS library page.
   *
   * @param string $url
   *   Library page url.
   * @param \BTJ\Scrapper\Container\LibraryContainerInterface $container
   *   Library container object.
   */
  public function libraryScrap(string $url, LibraryContainerInterface $container) : void {
    $crawler = $this->getTransport()->request('GET', $url);

    $crawler->filter('h1.page-title')->each(function ($node) use ($container) {
      $container->setTitle($node->text());
    });

    $crawler->filter('.content-image > img')->each(function ($node) use ($container) {
      $container->setTitleImage($node->attr('src'));
    });

    $crawler->filter('.template .zone-1')->each(function ($node) use ($container) {
      $body = '';
      $children = $node->children()->each(function ($child) {
        if ($child->nodeName() == 'script') {
          unset($child);
        }
        if (!empty($child)) {
          return $child->html();
        }
      });

      $children = array_filter($children, function ($child) {
        if (!empty($child)) {
          if (strpos($child, '<strong>') === FALSE) {
            return $child;
          }
        }
      });
      $container->setBody(implode('', $children));
    });
  }

}