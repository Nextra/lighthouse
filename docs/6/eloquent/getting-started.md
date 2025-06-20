# Eloquent: Getting Started

Lighthouse makes it easy for you to perform queries and mutations on your Eloquent models.

## Defining Models

Eloquent models usually map directly to GraphQL types.

```graphql
type User {
  id: ID!
  name: String!
}
```

It is strongly advised to name the field that corresponds to your primary key `id`.
Popular client libraries such as Apollo provide out-of-the-box caching if you follow that convention.

## Retrieving Models

Instead of defining your own resolver manually, you can rely on Lighthouse to build the Query for you.

```graphql
type Query {
  users: [User!]! @all
}
```

The [@all](../api-reference/directives.md#all) directive will assume the name of your model to be the same as
the return type of the Field you are trying to resolve and automatically uses Eloquent to resolve the field.

The following query:

```graphql
{
  users {
    id
    name
  }
}
```

Will return the following result:

```json
{
  "data": {
    "users": [
      { "id": 1, "name": "James Bond" },
      { "id": 2, "name": "Madonna" }
    ]
  }
}
```

## Pagination

You can leverage the [@paginate](../api-reference/directives.md#paginate) directive to
query a large list of models in chunks.

```graphql
type Query {
  posts: [Post!]! @paginate
}
```

The schema definition is automatically transformed to this:

```graphql
type Query {
  posts(first: Int!, page: Int): PostPaginator
}

type PostPaginator {
  data: [Post!]!
  paginatorInfo: PaginatorInfo!
}
```

And can be queried like this:

```graphql
{
  posts(first: 10) {
    data {
      id
      title
    }
    paginatorInfo {
      currentPage
      lastPage
    }
  }
}
```

## Adding Query Constraints

Lighthouse provides directives such as [@eq](../api-reference/directives.md#eq)
to enhance your queries with additional constraints.

The following field definition allows clients to find a user by their email:

```graphql
type Query {
  user(email: String! @eq): User @find
}
```

Query the field like this:

```graphql
{
  user(email: "chuck@nor.ris") {
    id
    name
  }
}
```

If found, the result will look like this:

```json
{
  "data": {
    "user": {
      "id": "69420",
      "name": "Chuck Norris"
    }
  }
}
```

## Ordering

Use the [@orderBy](../api-reference/directives.md#orderby) directive to sort
a result list by one or more given columns.

## Local Scopes

[Local scopes](https://laravel.com/docs/eloquent#local-scopes) are commonly used in Eloquent models
to specify reusable query constraints.

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }
}
```

Directives that query models, such as [@all](../api-reference/directives.md#all)
or [@first](../api-reference/directives.md#first), allow you to re-use those scopes:

```graphql
type Query {
  users: [User]! @all(scopes: ["verified"])
}
```

This query will produce the following SQL:

```sql
SELECT * FROM `users` WHERE `email_verified_at` IS NOT NULL
```

## Create

The easiest way to create data on your server is to use the [@create](../api-reference/directives.md#create) directive.

```graphql
type Mutation {
  createUser(name: String!): User! @create
}
```

This mutation will use the arguments passed to the field to create a new model instance:

```graphql
mutation {
  createUser(name: "Donald") {
    id
    name
  }
}
```

The newly created user is returned as a result:

```json
{
  "data": {
    "createUser": {
      "id": "123",
      "name": "Donald"
    }
  }
}
```

To create multiple models at once, use the [@createMany](../api-reference/directives.md#createMany) directive.

## Update

You can update a model with the [@update](../api-reference/directives.md#update) directive.

```graphql
type Mutation {
  updateUser(id: ID!, name: String): User! @update
}
```

Since GraphQL allows you to update just parts of your data, it is best to have all arguments except `id` as optional.

```graphql
mutation {
  updateUser(id: "123", name: "Hillary") {
    id
    name
  }
}
```

```json
{
  "data": {
    "updateUser": {
      "id": "123",
      "name": "Hillary"
    }
  }
}
```

The update may fail to find the model you provided and return `null`:

```json
{
  "data": {
    "updateUser": null
  }
}
```

To update multiple models at once, use the [@updateMany](../api-reference/directives.md#updatemany) directive.

## Upsert

Use the [@upsert](../api-reference/directives.md#upsert) directive to update a model with
a given `id` or create it if it does not exist.

```graphql
type Mutation {
  upsertUser(id: ID, name: String!, email: String): User! @upsert
}
```

Since upsert can create or update your data, your input should mark the minimum required fields as non-nullable.

```graphql
mutation {
  upsertUser(id: "123", name: "Hillary") {
    id
    name
    email
  }
}
```

```json
{
  "data": {
    "upsertUser": {
      "id": "123",
      "name": "Hillary",
      "email": null
    }
  }
}
```

To upsert multiple models at once, use the [@upsertMany](../api-reference/directives.md#upsertmany) directive.

## Delete

Deleting models is a breeze using the [@delete](../api-reference/directives.md#delete) directive. Dangerously easy.

```graphql
type Mutation {
  deleteUser(id: ID! @whereKey): User @delete
}
```

Simply call it with the ID of the user you want to delete.

```graphql
mutation {
  deleteUser(id: "123") {
    secret
  }
}
```

This mutation will return the deleted object, so you will have a last chance to look at the data. Use it wisely.

```json
{
  "data": {
    "deleteUser": {
      "secret": "Pink is my favorite color!"
    }
  }
}
```
