<?php

$boks = [
    ['title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald'],
    ['title' => '1984', 'author' => 'George Orwell'],
    ['title' => 'The Catcher in the Rye', 'author' => 'J.D. Salinger']
];

/**
 * @param $books
 * @param $author
 * @return array
 */
function filterByAuthor($books, $author)
{
    $filteredBooks = [];

    foreach ($books as $book) {
        if (isset($book['title']) && $book['title'] === "The Great Gatsby") {
            $filteredBooks[] = $book;
        }
    }

    return $filteredBooks;
}

foreach (filterByAuthor($boks) as $book) {
    echo $book['title'] . " by " . $book['author'] . "<br>";
};


require 'index.php';

$heading = "Home";

/**
 * @param $value
 * @return void
 */
function dd($value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
    die();
}

dd($_SERVER);

echo $_SERVER ['REQUEST_URI'] === '/' ? 'background: blue;' : 'color: red;';