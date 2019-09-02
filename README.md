![wp-graphql-logo-image]

# WPGraphQL Polls

This plugin extends the [WPGraphQL][wp-graphql-url] & [WP-Polls][wp-polls-url] plugins to provide and provides the interface for consuming the WP-Polls through queries and mutations.

This plugin is possible because of the work of [Adrien Becuwe][adrien-becuwe-url] & [7aduta][7aduta-url]. Please, don't forget to checkout their respective work.

[Wp-Polls Rest Api by Adrien Becuwe][wp-polls-rest-api-by-adrien-url]

[Wp-Polls Rest Api by 7aduta][wp-polls-rest-api-by-7aduta-url]

## Install, Activate & Setup

You can install and activate the plugin like any WordPress plugin. Download the .zip from Github and add to your plugins directory, then activate.

It requires you to have WP-Polls plugin first in order to work. You can get it <a href="https://github.com/lesterchan/wp-polls" target="_blank">here</a>.

## Voting

This plugin add a new `vote` mutation to the WPGraphQL Schema.

This can be used like so:

```
mutation PollVote {
  vote(input: {
    clientMutationId: "Vote",
    id: 1,
    userId: 1,
    answers: "1,2,3"
  }) {
    status
    message
  }
}
```

- The integer `id` is unique identifier for the poll you are voting.
- The integer `userId` is the identifier of the voter.
- The string `answers` are the `ids` of the user selected answers. Note: they have to be comma separated only.

It returns a status code and a respective message after the operation has been executed or not.

## Querying

You can query polls by doing the following query:

```
query GetAllPolls {
  polls {
    id
    question
    totalVotes
    open
    maxAnswers
    voted
    answers {
      id
      description
      votes
    }
  }
}
```

- `id`: (integer) is the unique identifier for the poll you are voting.
- `question`: (string) is a text describing the poll.
- `totalVotes`: (integer) is the total amount of votes in this poll.
- `open`: (boolean) is the current status of the poll. Telling either if it's available for voting or not.
- `maxAnswers`: (integer) is the number of answers that can be selected per user before voting.
- `voted`: (boolean) tells if the user has already voted in this poll.
- `answers`: (array) is an array of object containing detailed information about the poll's options.
- - `id`: (integer) is the unique identifier for the respective answer.
- - `description`: (string) is a text representing an option for voting.
- - `votes`: (integer) is the unique identifier for the poll you are voting.

If you need to query an specific poll you can do it by passing the poll `id` through the query, like the example bellow:

```
query GetPollById {
  polls(id: 1) {
    ...
  }
}
```

## Status

The statuses returned by the vote mutation are normal HTTP codes. The ones used in this plugin are:

- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `409`: Conflict
- `500`: Internal Server Error

## Thanks

I'd like to say many thanks to [WPGraphQL][wp-graphql-url], [Lester Chan][lester-chan-url], [Adrien Becuwe][adrien-becuwe-url] & [7aduta][7aduta-url] for their work.

[wp-graphql-url]: https://github.com/wp-graphql/wp-graphql
[wp-polls-url]: https://github.com/lesterchan/wp-polls
[lester-chan-url]: https://github.com/lesterchan
[adrien-becuwe-url]: https://github.com/adrinoe
[7aduta-url]: https://github.com/7aduta
[wp-polls-rest-api-by-adrien-url]: https://github.com/adrinoe/wp-polls-rest-api
[wp-polls-rest-api-by-7aduta-url]: https://gist.github.com/7aduta/2bfe5788fa2186255ebe1339ed01fb37
[wp-graphql-logo-image]: https://www.wpgraphql.com/wp-content/uploads/2017/06/wpgraphql-logo-e1502819081849.png
