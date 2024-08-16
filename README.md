### NightfallProtocols

(not-so) Simple multi-version base to support older versions.

Replacement of [NetherGames MultiVersion](https://github.com/NetherGamesMC/PocketMine-MP) for people who can't use it

### Versions

Currently, the plugin supports these versions:
- 1.21.20(`712`)
- 1.21.2 (`686`)
- 1.21.0 (`685`)
- 1.20.80 (`671`)
- 1.20.70 (`662`)
- 1.20.60 (`649`)

### Contribution

You can contribute with these templates

##### FOR ISSUES:
```yml
Issue name: (Problem)
Issue description:

- Version: X.XX.XX
- Server Version: X.XX.X 
# ANY REPORTS WHERE THE SERVER VERSION IS
# DIFFERENT FROM THE PLUGIN VERSION WILL BE REMOVED
- Expected: (Explanation of the expected behaviour)
- Actual: (Explanation of the actual behaviour)
```

Should look something like this
```yml
Issue name: Item stack isn't being downgraded properly
Issue description:

- Version: 1.20.60
- Server Version: 1.21.2
- Expected: Item stacks to be normal
- Actual: Item stacks are in-correctly or even not downgraded
```

##### FOR PULL REQUESTS:
```yml
PR name: (Small description of the PR)
PR description:

- Changes: (Description of the changes in the PR)
- Affected Versions: ALL/X.XX.XX, X.XX.Y

# THESE SHALL BE TESTED !11!1 
```
Should look something like this
```yml
PR name: Added 1.20.50
PR description:

- Changes: Adds packet translation and batch compression algorithm for 1.20.50
- Affected Versions: 1.20.50
```

These can  be simplified if it doesn't affect much.

### Huge thanks to

- [Flonja](https://github.com/Flonja) for bearing with me over the months and helping with previous attempts as well as his [multiversion](https://github.com/Flonja/multiversion)
- [glance](https://github.com/glancist) for the help with un-seen item translation (crafting data and creative content packets)