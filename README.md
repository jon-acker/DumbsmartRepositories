## Dumbsmart Repositories

This project is meant to provide a layer in between your project and a collection of fake repositories
so that each document is stored into its own repository regardless if it was persisted as a relation for
another object.
This layer is designed to be used on testing environments or on low data-level-access applications. Using this
project in production is totally discouraged.

## Why?

When using fake repositories is common to use function like `serialize` or `json_encode` in order to make
data easily persistable. The side effects of this methods is that related documents are stored along with
the parent document so if you query this document in its own repository produces no results and modifying
its data means to load any potential document that relates with the one that must me modified.
This is usually not a problem for small projects but on large projects, data used to be complex enough to
have annoying issues due to this. 

### Installation

Install with:
```
$ composer require --dev carlosv2/dumbsmart-repositories
```

### Usage

Imagine you have the following entities:
```
class User
{
    private $id;
    private $posts;
    
    public function __construct($id) { $this->id = $id; }
    public function getId() { return $this->id; }
    public function setPosts(array $posts) { $this->posts = $posts; }
}

class Post
{
    private $id;
    
    public function __construct($id) { $this->id = $id; }
    public function getId() { return $this->id; }
}
```

In order to use them, you first need to configure this layer:
```
// Configure the metadata
$metadataManager = new MetadataManager();

$userMetadata = new Metadata(new AccessorObjectIdentifier('getId'));
$userMetadata->setRelation(new OneToManyRelation('posts'));
$metadataManager->addMetadata(User::class, $userMetadata);

$postMetadata = new Metadata(new AccessorObjectIdentifier('getId'));
$metadataManager->addMetadata(Post::class, $postMetadata);


// Configure the repositories
$repositoryManager = new RepositoryManager();
$repositoryManager->addRepository(User::class, new InMemoryRepository(new AccessorObjectIdentifier('getId')));
$repositoryManager->addRepository(Post::class, new InMemoryRepository(new AccessorObjectIdentifier('getId')));


// Create the persister object
$transactionFactory = new TransactionFactory($metadataManager, $repositoryManager);
$persister = new Persister($repositoryManager, $transactionFactory);
```

Once you have configured the layer, you can start using it like this:
```
$post1 = new Post(1);
$post2 = new Post(2);

$user = new User(1);
$user->setPosts([$post1, $post2]);

$persister->save($user);

// This returns an object with same properties as $post2. However it does
// not return same object because it has been serialized and unserialized
$persister->findById(Post::class, 2);
```