# Kaleido: A schema manager for Laravel

This project provides a powerful Domain-Specific Language (DSL) to define your database schema for Laravel applications. It allows you to define your models, fields, and relationships in a single, human-readable file. 

The tool automatically generates Laravel migrations and Eloquent models based on your schema definition, streamlining your development workflow.

## Table of Contents

- [Core Concepts](#core-concepts)
- [Defining Models](#defining-models)
- [Defining Fields](#defining-fields)
  - [Data Types](#data-types)
- [Field Attributes](#field-attributes)
- [Defining Relationships](#defining-relationships)
  - [One-to-Many (`belongsTo`)](#one-to-many-belongsto)
  - [Many-to-Many (`manyToMany`)](#many-to-many-manytomany)
- [Special Helpers](#special-helpers)
- [Full Example](#full-example)

---

## Core Concepts

The entire database schema is defined in a single file, typically `database/schema.kdl`.

The system works by:
1.  Parsing the `schema.kdl` file.
2.  Comparing it to the last known state of your schema.
3.  Generating migrations for any detected changes (new tables, columns, indexes, etc.).
4.  Creating or updating the corresponding Eloquent models with fillable attributes and relationship methods.

## Defining Models

Each model in your application is represented by a `model` block. The model name should be singular and capitalized (e.g., `User`, `Post`, `Role`).

```kdl
model User {
  # ... fields and relationships go here
}

model Post {
  # ... fields and relationships go here
}
```

## Defining Fields

Inside a model block, you define its fields using the `fieldName: type` syntax.

```kdl
model User {
  id: ulid
  name: string
  email: string
  age: integer
}
```

### Data Types

The DSL supports all standard Laravel migration column types:

- `string`
- `text`
- `integer`
- `bigInteger`
- `boolean`
- `timestamp`
- `date`
- `ulid`
- `uuid`
- `foreignId`
- `decimal`
- `float`
- `json`

## Field Attributes

To add constraints or modify a field's behavior, you can use `@` attributes. Attributes are placed directly after the field type.

```kdl
model Product {
  id: ulid @primary
  sku: string @unique
  description: text @nullable
  status: string @default('available')
  price: decimal(8, 2)
}
```

- `@primary`: Marks the field as the primary key.
- `@unique`: Adds a unique index to the column.
- `@nullable`: Allows the column to have `NULL` values.
- `@default(value)`: Sets a default value for the column. The value must be enclosed in single quotes for strings.

Multiple attributes can be chained:
`api_token: string @unique @nullable`

## Defining Relationships

Eloquent relationships are defined in a clear, declarative way.

### One-to-Many (`belongsTo`)

To define a one-to-many relationship, use the `belongsTo(ModelName)` syntax. This will automatically create the necessary foreign key column.

```kdl
model Post {
  id: ulid @primary
  title: string
  # This creates a 'user_id' column and sets up the foreign key.
  author: belongsTo(User)
}

model User {
  # ... other fields
  # The tool will automatically add the hasMany('posts') relationship to the User model.
}
```

You can also make relationships nullable:
`author: belongsTo(User) @nullable`

### Many-to-Many (`manyToMany`)

Use the `manyToMany(ModelName)` syntax to define a many-to-many relationship. The tool will automatically create the required pivot table.

```kdl
model Post {
  id: ulid @primary
  title: string
  tags: manyToMany(Tag)
}

model Tag {
  id: ulid @primary
  name: string @unique
  posts: manyToMany(Post)
}
```

This will create a `post_tag` pivot table and add the `belongsToMany` relationship methods to both the `Post` and `Tag` models.

## Special Helpers

- `timestamps`: A shorthand to automatically add `created_at` and `updated_at` timestamp columns to your model.

```kdl
model Category {
  id: ulid @primary
  name: string
  timestamps
}
```

## Full Example

Here is a complete `schema.kdl` example:

```kdl
# /database/schema.kdl

model User {
  id: ulid @primary
  name: string
  email: string @unique
  password: string
  role: belongsTo(Role) @nullable
  timestamps
}

model Role {
  id: ulid @primary
  label: string @unique
  timestamps
}

model Post {
  id: ulid @primary
  title: string
  content: text
  author: belongsTo(User)
  tags: manyToMany(Tag)
  published_at: timestamp @nullable
  timestamps
}

model Tag {
  id: ulid @primary
  name: string @unique
  posts: manyToMany(Post)
  timestamps
}
```