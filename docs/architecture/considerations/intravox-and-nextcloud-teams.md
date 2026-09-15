# IntraVox and Nextcloud Teams: Layers, Not Rivals

> **Document type**: Architectural consideration
> **Purpose**: Define where IntraVox sits now that Nextcloud Teams is becoming the unified team workspace — which integrations we will build, and which aggregation we deliberately will not
> **Audience**: Contributors, integrators, and anyone proposing a feature that pulls other apps' content into an IntraVox page
> **Status**: Current as of Nextcloud 35. Nextcloud's Teams work is active and dated statements here may age.

---

## Executive Summary

Nextcloud 35 turns Teams into a workspace that owns resources and shows them in one place. That raises a fair question for IntraVox: if Teams aggregates what a team is working on, what is left for us?

The answer is that Teams and IntraVox answer different questions.

| | Nextcloud Teams | IntraVox |
|---|---|---|
| **Question answered** | "What is my team working on right now?" | "What has the organization published?" |
| **Scope** | One team | Whole organization, or a department |
| **Audience** | Members of that team | Everyone who should read it, member or not |
| **Content** | Live resources: chat, boards, files, calendars | Edited, versioned, published pages |
| **Lifecycle** | Ephemeral — it reflects current state | Deliberate — draft, review, publish, expire |
| **Composition** | A list of resources the team owns | An editorial layout someone designed |

Teams is a **workspace**. IntraVox is a **publication**. A team page tells you where to work; an intranet page tells you what was decided, and why.

Our position follows from that:

1. **We integrate with Teams by registering as a resource provider**, so IntraVox pages a team owns appear on the team page. This is the right kind of integration: we publish a list, Nextcloud renders it.
2. **We do not rebuild Teams aggregation inside IntraVox.** Pulling Talk messages or Deck cards into widgets duplicates something Nextcloud does better, and it forces us to hardcode other apps' URL schemes.
3. **Where a widget shows content from another system, that system must own the link.**

Principle 3 is the one that decides feature requests, so the rest of this document explains it.

---

## Why Teams changed the question

Until recently a team was, in Nextcloud's own words, "just a list of people that other apps can share to." In Nextcloud 35 the Teams app moved out of Contacts, gained its own frontend, and started provisioning a team folder on creation. The [public roadmap](https://github.com/nextcloud/circles/issues/2664) states the intent plainly:

> Teams becomes Nextcloud's unified team workspace: the place where a group of people gets all the resources they need to collaborate (files, chat, knowledge base, tasks, data) provisioned together, governed together, and surfaced together.

Two consequences matter to us.

**The team page is provider-driven, and that is an open door.** It calls the core endpoint `GET /ocs/v2.php/teams/{teamId}/resources`, which asks every app that registered an `OCP\Teams\ITeamResourceProvider` what it has for that team. Talk, Deck, Collectives and Files all answer. Any app can. This is public API, stable since Nextcloud 29.

**The creation menu is not.** The "Add to team" menu is a hardcoded list in the Teams frontend — team folder, collective, page, Deck board — with a "More coming soon" caption that is a literal placeholder, not an extension point. Nextcloud scoped a generic "create a resource in a team" API out of the original epic and has not reopened it.

So third-party apps can be *listed* on a team page, but cannot yet add an entry to its create menu. For IntraVox that asymmetry is acceptable: being found matters more than being created from there.

---

## The dividing line: who owns the URL

Every app knows how to link to its own content. Talk knows that a message is `/call/{token}#message_{id}`. Forms knows its own hash route. Deck knows its board and card ids. That knowledge lives in the app, changes with the app, and is versioned with the app.

The moment IntraVox composes another app's URLs from raw API fields, we take a copy of knowledge that lives elsewhere. The copy is correct only until upstream changes a route — and then it breaks silently, on a customer's intranet, with no test that could have caught it.

This gives us a test that resolves most "can IntraVox show X?" requests:

> **Does the source system expose the link, or would IntraVox have to construct it?**
>
> If the source exposes it — a feed `<link>`, an API field with a web URL — a widget is appropriate.
> If IntraVox would have to construct it from ids and literal path fragments, the integration belongs on the other side.

The Feed widget respects this for external systems. RSS items carry their own links. Jira, Confluence, OpenProject and the LMS connectors return URLs, or are close enough that a preset can normalise them. Those systems are outside Nextcloud, have no Teams integration, and will never get one — a widget is the only option, so we accept the maintenance.

Nextcloud-internal sources are a different case, because there the alternative exists. Talk already implements `ITeamResourceProvider` and returns absolute, correct URLs generated by Talk itself. A Talk widget in IntraVox would be a worse version of something Nextcloud already ships — worse because our copy of the URL scheme can drift, and theirs cannot.

This is why the Feed widget has connectors for Moodle, Jira and SharePoint but none for Talk, Forms or Deck. That is not an oversight to be corrected. It is the line.

---

## How this extends the "inherit, don't reimplement" principle

[Nextcloud-Native Architecture](nextcloud-native-architecture.md) makes the case that IntraVox inherits a dozen enterprise features — ACLs, versioning, sharing, audit, trash, quota — because a page is a folder. We do not reimplement them; we let Nextcloud do the work and stay correct for free.

Deep links are the same argument applied to navigation instead of storage. Talk's route is Talk's business, exactly as file versioning is Files' business. Reimplementing either produces something that works in a demo and rots in production.

[Collectives Comparison](collectives-comparison.md) drew a related line between broadcast and collaboration and concluded both apps should stay separate with integration points, rather than one absorbing the other. Teams does not change that conclusion; it supplies the shared surface those integration points were missing. Worth noting, though: the team page's built-in "New page" creates a **Collectives** page. Collectives is being positioned as the team's default knowledge base. IntraVox's answer is not to compete for that slot but to be the layer above it — what the organization publishes, rather than what one team drafts.

---

## What we will build

### 1. Register IntraVox as a team resource provider

Implement `OCP\Teams\ITeamResourceProvider` so that IntraVox pages belonging to a team appear on that team's page, with the correct label, icon, and URL — generated by IntraVox, which is the only component that should generate them.

This is small: one class implementing six methods, plus one `registerTeamResourceProvider()` call at boot. Pages already live in Team folders, so the team-to-content mapping largely exists.

Open design question: pages in a team folder are the obvious candidates, but a team can own resources without having a team folder. The provider must decide what it reports in that case rather than assuming a folder exists.

### 2. Make the URL contract explicit in the Feed widget

Two changes, both driven by [#114](https://github.com/nextcloud/IntraVox/issues/114):

**An item with no link must not pretend to have one.** Today a feed item renders `<a href="">`, and an empty `href` resolves to the current document — so the item links back to the IntraVox page it sits on. That is a bug regardless of any other decision here, and it affects every connection whose URL mapping is empty or mismapped.

**Custom connections need to compose URLs, not only extract them.** Response mapping resolves a single dot-path per field, which works when an API returns a usable web URL and fails when the link must be assembled from several fields. The pattern is already proven inside IntraVox: the Jira and OpenProject connectors compose URLs in PHP, hardcoded per type. Exposing that as a configurable template generalises what we already do and removes two special cases.

Implementers should note that the existing `sanitizeJsonPath()` validator accepts only `[a-zA-Z0-9_.]` and silently returns an empty string otherwise. A URL template contains characters that validator rejects, so a template belongs in its own field with its own validation — widening the shared validator would weaken path sanitisation for the six other mapping fields.

### 3. Keep Nextcloud-internal aggregation out of the widget set

No Talk widget, no Deck widget, no Forms widget that reconstructs deep links from API responses. Where a customer wants team activity on an intranet page, the answer is the team page, and — where the gap is real — an upstream contribution so the source app reports itself properly.

---

## What we will not build

**A Talk connector.** Talk already reports itself to Teams with correct links. A connector would duplicate that with a copy of Talk's route that can drift.

**Team-scoped widget context.** Widgets bind their sources at authoring time: a People widget names groups, a Calendar widget names calendars. A "current team" context that made one page render differently per team would turn a published page into a template with no fixed content — which is what a team page already is, done properly. Pages stay explicit.

**Team-level policy or governance.** A "do not share outside this team" rule has to be enforced in the sharing layer itself, or apps can ignore it. That is Nextcloud's to build, and it is on their roadmap as [sharing lockdown per team](https://github.com/nextcloud/circles/issues/2645). IntraVox should respect such policy once it exists, never attempt to define it.

---

## Honest limitations

**Forms has no Teams answer today.** Nextcloud Forms does not implement a team resource provider and has no open issue to do so; it appears only as an unchecked item on the [team-owned resources](https://github.com/nextcloud/circles/issues/2644) list. So for forms, "use Teams instead" is not yet available advice. This is exactly the case the URL-template work in §2 serves: an external or sibling app that exposes its own hash or id, where IntraVox supplies the template once rather than hardcoding a connector.

**Calendar is worse off.** The Calendar app's team resource provider [has been open since March 2024](https://github.com/nextcloud/calendar/issues/5842) with no milestone. Our Calendar widget therefore overlaps with what a team page should eventually show. We keep the widget: it serves an editorial purpose — a chosen set of calendars presented on a published page — that a team resource list does not replace.

**The roadmap is intent, not commitment.** Everything cited here about Nextcloud 36 comes from an open, work-in-progress GitHub issue. Team ownership of resources, custom roles and sharing lockdown are planned, largely undated, and may change. We should integrate against what has shipped — `ITeamResourceProvider`, stable since 29 — and treat the rest as direction.

**Being listed is not being adopted.** A provider gets IntraVox onto the team page. It does not make IntraVox the obvious place to write a team's pages; the built-in menu still points at Collectives. Winning that position is a product question, not an integration one.

---

## Summary

Teams answers "what is my team working on". IntraVox answers "what has the organization published". Teams aggregates resources; we publish pages. Where those meet, the rule is that whoever owns the content owns the link to it: we hand Nextcloud a list of our pages and let it render them, and we do not hand ourselves other apps' URL schemes to maintain.

That principle costs us a Talk widget we could have built. It buys us integrations that keep working when upstream changes.

---

## Related documentation

- [Nextcloud-Native Architecture](nextcloud-native-architecture.md) — the inherit-don't-reimplement principle this document extends
- [Collectives Comparison](collectives-comparison.md) — broadcast versus collaboration, and why both apps stay separate
- [SharePoint Comparison](../sharepoint-comparison.md) — concept mapping for readers coming from SharePoint
- [Feed Widget](../../features/feed-widget.md) — connection types, response mapping, and presets
