<?php

/*
 * CS313 TA05 Main Controller
 */

// Create or access a Session
session_start();

// Set the loggedin session variable to false
//if (!isset($_SESSION['loggedin'])) {
 $_SESSION['loggedin'] = FALSE;
//}

// Get the database connection file
require_once 'library/connections.php';

// Get the miscellaneous functions file
require_once 'library/functions.php';

// Get the miscellaneous functions file
require_once 'model/model.php';

$action = filter_input(INPUT_POST, 'action');
if ($action == NULL){
  $action = filter_input(INPUT_GET, 'action');
}

switch ($action){
    case 'find':
        $author = filter_input(INPUT_POST, 'srcAuthor', FILTER_SANITIZE_STRING);
        $title = filter_input(INPUT_POST, 'srcTitle', FILTER_SANITIZE_STRING);
        if(empty($author) && empty($title)){
            $searchresults = listAllBooks();
        }
        if(!empty($author) && !empty($title)) {
            $searchresults = getBooksByAuthorTitle($author,$title);
        } else {
            if(!empty($author)){
                $searchresults = getBooksByAuthor($author);
            } else {
                $searchresults = getBooksByTitle($title);
            }
        }
        $results = buildResultsTable($searchresults);
        include 'view/main.php';
        break;
    case 'home':
        include 'view/main.php';
        break;
    case 'authors':
        include 'view/authors.php';
        break;
    case 'listauthors':
        $authorList = listAuthors();
        $results = buildAuthorTable($authorList);
        include 'view/authors.php';
        break;
    case 'addauthor':
        $authorName = filter_input(INPUT_POST, 'authorName', FILTER_SANITIZE_STRING);
        if(!empty($authorName)) {
            $results = getAuthor($authorName);
            if(count($results) > 0) {
                $_SESSION['message']="Author " . $authorName . " already exists.";
                header('location: index.php?action=authors');
                exit;
            } else {
                $insOutcome = insertAuthor($authorName);
                if($insOutcome === 1){
                    $_SESSION['message'] = "$authorName has successfully been added";    
                    unset($results);    
                } else {
                    $_SESSION['message'] = "$authorName could not be added.  Please try again.";
                }
            }   
        } else {
            $_SESSION['message'] = 'Please provide author name.';
            header('location: index.php?action=authors');
            exit; 
        }
        include 'view/authors.php';
        break;
    case 'delauthor':
        $authorId = filter_input(INPUT_POST, 'authorId');
        $authorName = filter_input(INPUT_POST, 'authorName', FILTER_SANITIZE_STRING);
        if(!empty($authorName)) {
            $results = getAuthor($authorName);
            if(count($results) < 1 ) {
                $_SESSION['message']="Author " . $authorName . " does not exist.";
                header('location: index.php?action=authors');
                exit;
            } else {
                $authorId = $results[0]['authorid'];
                if(empty($authorId)){
                    $authorId = $results['authorid'];
                }
                $results = findBooksByAuthorId($authorId);
                if(count($results)>0){
                    $_SESSION['message'] = "Books by $authorName exist.  Remove all books for this author and try again.";
                    header('location: index.php?action=authors');
                    exit;
                }
                $delOutcome = deleteAuthor($authorId);
                if($delOutcome === 1){
                    $_SESSION['message'] = "$authorName has successfully been deleted";    
                    unset($results);    
                } else {
                    $_SESSION['message'] = "$authorName could not be deleted.  Please try again.";
                }
            }   
        } else {
            $_SESSION['message'] = 'Please provide author name.';
            header('location: index.php?action=authors');
            exit; 
        }
        include 'view/authors.php';
        break;
    case 'books':
        $authors = listAuthors();
        $authorList = buildAuthorDropDown($authors);
        include 'view/books.php';
        break;
    case 'addbook':
        $bookTitle = filter_input(INPUT_POST, 'bookTitle', FILTER_SANITIZE_STRING);
        $bookDesc = filter_input(INPUT_POST, 'bookDesc', FILTER_SANITIZE_STRING);
        //$bookAuthor = filter_input(INPUT_POST,'bookAuthor', FILTER_SANITIZE_STRING);
        $authorId = $_POST['bookAuthor'];
        if(empty($bookTitle) || empty($bookDesc) || empty($authorId)){
            $_SESSION['message'] = 'Please provide information for all empty form fields.';
            header('location: index.php?action=books');
            exit; 
        }
        $results = getAuthorTitle($authorId,$bookTitle);
        if(!empty($results)){
            $_SESSION['message'] = "Title " . $bookTitle . " already exists.";
            header('location: index.php?action=books');
            exit;
        } 
        $insOutcome = insertBook($bookTitle,$bookDesc,$authorId);
        if($insOutcome === 1){
            $_SESSION['message'] = "$bookTitle has successfully been added";        
        } else {
            $_SESSION['message'] = "$bookTitle could not be added.  Please try again.";
        }
        header('location: index.php?action=books');
        exit;
    case 'updbook':
        $authorId = $_POST['bookAuthor'];
        $bookTitle = filter_input(INPUT_POST, 'bookTitle', FILTER_SANITIZE_STRING);
        $bookDesc = filter_input(INPUT_POST, 'bookDesc', FILTER_SANITIZE_STRING);
        $found = getExactTitle($bookTitle);
        if(count($found) < 1) {
            $_SESSION['message'] = "$bookTitle was not found.  Please re-enter title or add book.";
            header('location: index.php?action=books');
            exit;
        } else {
            $bookId = $found[0]['bookid'];
            if(empty($bookDesc)) {
                $bookDesc = $found[0]['description'];
            }
            date_default_timezone_set('America/New_York');
            $modDate = date('Y-m-d');
            $updOutcome = updateBookInfo($bookId,$authorId,$bookDesc,$modDate);
            if($updOutcome === 1) {
                $_SESSION['message'] = "$bookTitle was updated successfully";
            } else {
                $_SESSION['message'] = "$bookTitle was not updated.  Please try again.";
            }
        }
        header('location: index.php?action=books');
        exit;
    case 'delbook':
        $authorId = $_POST['bookAuthor'];
        $bookTitle = filter_input(INPUT_POST, 'bookTitle', FILTER_SANITIZE_STRING);
        $found = returnBookId($authorId,$bookTitle);
        if(count($found) < 1) {
            $_SESSION['message'] = "$bookTitle/$bookAuthor was not found.  Please re-enter your entries.";
            header('location: index.php?action=books');
            exit;
        }
        $bookId = $found[0]['bookId'];
        if(empty($bookId)) {$bookId = $found[0]['bookid'];}
        $delOutcome = deletebook($bookId);
        if($delOutcome === 1){
            $_SESSION['message'] = "$bookTitle has successfully been deleted";        
        } else {
            $_SESSION['message'] = "$bookTitle could not be deleted.  Please try again.";
        }
        header('location: index.php?action=books');
        exit;
    default:
            include 'view/main.php';
}
?>

