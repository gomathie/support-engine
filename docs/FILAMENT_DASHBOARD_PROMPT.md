# Prompt: build a scoped Filament admin dashboard

Copy everything below the line into your coding agent, working in the target
Filament app. Replace the bracketed placeholders first — the rest is
domain-agnostic.

---

## Task

Build an admin dashboard for this Filament panel. Three widgets and a set of
navigation badges. Do not invent domain concepts — read the existing models,
migrations and policies first and map my terms onto whatever this app actually
calls them.

### Placeholders to resolve before you start

| Placeholder | Means | Example in my source app |
| --- | --- | --- |
| `[SUBJECT]` | The people the dashboard reports on | employees |
| `[ITEM]` | The thing they are assigned | courses |
| `[ASSIGNMENT]` | The join between them, with a deadline | course enrollments |
| `[ROLLUP]` | A per-subject-per-item progress row | `course_progress` |
| `[SCOPE]` | What limits a non-admin's visibility | department |
| `[SCORE]` | An optional numeric outcome | quiz score |

If this app has no `[ROLLUP]` table — no denormalised progress row — say so and
stop before writing the widgets. Computing percentages across every assignment
on every dashboard render does not scale, and the fix is a rollup table
maintained by whatever already mutates progress, not a clever query. That is a
separate piece of work and I want to decide on it explicitly.

### Widget 1 — stats overview

A `StatsOverviewWidget`, full column span, roughly seven `Stat`s. Mine were:

- Total `[SUBJECT]`s, with active count as the description
- Published `[ITEM]`s, with total as the description
- Completion rate as a percentage, coloured success / warning / danger by band
- In progress
- Average `[SCORE]`, or `—` when there are no results yet
- Overdue, coloured danger when above zero
- Certificates or equivalent terminal artefacts issued

Every figure must go through **one** scoping helper (see Scoping below) rather
than each stat rolling its own `where`. A new stat added later must not be able
to leak across `[SCOPE]` by forgetting a clause.

### Widget 2 — completion by `[SCOPE]`

A bar `ChartWidget`. One bar per `[SCOPE]`, value = percentage completed.

Fetch it as a **single grouped aggregate**, not a query per bar:

```php
->selectRaw('[subjects].[scope]_id, [rollup].status, count(*) as total')
->groupBy('[subjects].[scope]_id', '[rollup].status')
```

then reduce in PHP. A `[SCOPE]` with no rows must render as 0, not divide by
zero or vanish.

### Widget 3 — the actionable list

A `TableWidget`: who is past their deadline and has not finished. This is the
only widget somebody acts on, so it is the one that must be correct.

Two rules that are easy to get wrong:

1. Exclude anyone who **completed late**. Overdue means outstanding, not
   "finished after the date". Use a `whereNotExists` against `[ROLLUP]` with
   status completed, correlated on both keys.
2. Paginate small — 5 by default. It is a dashboard panel, not a report.

### Scoping — the part that matters

Non-admins must see only their own `[SCOPE]`. Put the rule in **one place** on
the user model:

```php
public function visibleScopeIds(): array
{
    if ($this->isAdmin())   { return Scope::query()->pluck('id')->all(); }
    if ($this->isManager()) { return $this->managedScopes()->pluck('scopes.id')->all(); }
    return [];
}
```

Then apply it in every widget query, and also in `getEloquentQuery()` on the
related Resources. Scoping the record but not the **list** is a real leak: a
manager who cannot open a record can still learn the size and composition of a
`[SCOPE]` they have no business seeing, straight off the table and its counts.

### Navigation badges

Add `getNavigationBadge()` to the Resources where a number means "somebody
should act":

- draft / unpublished `[ITEM]`s — warning
- overdue `[ASSIGNMENT]`s — danger
- anything queued for human review — warning

Give each a `getNavigationBadgeTooltip()`. A bare number in a sidebar is a
riddle. Scope the badge counts the same way as everything else.

### Panel configuration

```php
->sidebarCollapsibleOnDesktop()
->maxContentWidth('full')
->globalSearchKeyBindings(['command+k', 'ctrl+k'])
```

Add `getGloballySearchableAttributes()` plus
`getGlobalSearchResultDetails()` to the two or three Resources people actually
hunt for. Search results without a qualifying detail line are ambiguous the
moment two records share a name.

---

## Traps I hit building this. Check each one.

These cost me real debugging time in the source app. They are Filament and
Eloquent behaviours, not domain quirks, so they will very likely bite here too.

1. **`Builder::value()` applies model casts.** `->value('status')` on a column
   cast to an enum returns the **enum**, not the string. A column callback
   written as `fn ($state) => Status::from($state)` then dies with
   "must be of type string|int, Status given". Type-hint the enum and use it
   directly.

2. **A relationship's own `orderBy` is not replaced, it is appended to.** If
   `modules()` is declared `->orderBy('position')`, then
   `$model->modules()->orderByDesc('position')->first()` orders ascending then
   descending and returns the **first** row. Query the model directly, or call
   `->reorder()`.

3. **`->options()` silently overrides `->relationship()` on a Select.** The
   relationship makes the value the related **id**; an options array keyed by
   name makes the widget offer **names**. Editing then produces a mixed array —
   the hydrated id plus the picked name — and the sync asks the database for
   `where id in (1, admin)`. Pick one source of options. To keep pretty labels
   with a relationship, use `getOptionLabelFromRecordUsing()`.

4. **`Resource::getUrl()` needs a default panel.** If the panel provider has no
   `->default()`, `getUrl()` throws `NoDefaultPanelSetException` outside a panel
   request — which includes every test.

5. **Records bound by slug need `getRouteKey()`, not `getKey()`.** Passing an id
   to `Livewire::test(EditThing::class, ['record' => ...])` throws
   `ModelNotFoundException` for any model overriding `getRouteKeyName()`.

6. **Never link to a Filament page with an SPA link component.** If this app has
   an Inertia or Livewire-SPA front end, use a plain `<a href>` for `/admin`. An
   Inertia `<Link>` XHRs it, gets HTML where it expects JSON, and renders the
   whole panel inside Inertia's error modal in an iframe, where no menu works.

## Tests — and one specific gap to close

Write tests that:

- render every widget as an admin, with seeded data (an empty table hides half
  the bugs)
- assert a manager sees their own `[SCOPE]` and **`assertDontSee`** a record
  from another one, on the list and on any report page
- assert a non-admin, non-manager gets 403 on the panel

Then close the gap that let a 500 reach me in production: **submit at least one
form, do not only render it.** Render tests assert a 200 and sail straight past
a form that displays perfectly and explodes on save. Use
`Livewire::test(...)->fillForm([...])->call('create')->assertHasNoFormErrors()`
and then assert the row actually landed with its relationships attached.

Include a test that saving with a blank password field leaves the existing
password intact, if this app has one. That is a silent data-loss path and
nothing else covers it.

## Style

Match the surrounding codebase — its naming, its comment density, its idiom.
Comment the *why* where a reader would otherwise wonder, not the *what*. Do not
add a package unless the task genuinely needs one.
