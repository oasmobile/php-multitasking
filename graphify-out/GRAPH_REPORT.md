# Graph Report - /Users/zhangminhao/git/mlib/multitasking  (2026-04-25)

## Corpus Check
- 15 files · ~13,626 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 114 nodes · 247 edges · 12 communities detected
- Extraction: 52% EXTRACTED · 48% INFERRED · 0% AMBIGUOUS · INFERRED: 118 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Community 0|Community 0]]
- [[_COMMUNITY_Community 1|Community 1]]
- [[_COMMUNITY_Community 2|Community 2]]
- [[_COMMUNITY_Community 3|Community 3]]
- [[_COMMUNITY_Community 4|Community 4]]
- [[_COMMUNITY_Community 5|Community 5]]
- [[_COMMUNITY_Community 6|Community 6]]
- [[_COMMUNITY_Community 7|Community 7]]
- [[_COMMUNITY_Community 8|Community 8]]
- [[_COMMUNITY_Community 9|Community 9]]
- [[_COMMUNITY_Community 10|Community 10]]
- [[_COMMUNITY_Community 11|Community 11]]

## God Nodes (most connected - your core abstractions)
1. `WorkerInfo` - 12 edges
2. `SharedMemoryTest` - 11 edges
3. `SharedMemory` - 11 edges
4. `Semaphore` - 10 edges
5. `BackgroundWorkerManager` - 10 edges
6. `MessageQueueTest` - 8 edges
7. `SemaphoreTest` - 7 edges
8. `MessageQueue` - 6 edges
9. `SharedMemoryPbtTest` - 5 edges
10. `MessageQueuePbtTest` - 5 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Communities

### Community 0 - "Community 0"
Cohesion: 0.22
Nodes (2): SharedMemory, SharedMemoryTest

### Community 1 - "Community 1"
Cohesion: 0.19
Nodes (2): SemaphorePbtTest, SemaphoreTest

### Community 2 - "Community 2"
Cohesion: 0.22
Nodes (2): MessageQueue, MessageQueueTest

### Community 3 - "Community 3"
Cohesion: 0.3
Nodes (1): BackgroundWorkerManager

### Community 4 - "Community 4"
Cohesion: 0.2
Nodes (1): WorkerInfo

### Community 5 - "Community 5"
Cohesion: 0.36
Nodes (1): Semaphore

### Community 6 - "Community 6"
Cohesion: 0.38
Nodes (1): WorkerManagerCompletedEvent

### Community 7 - "Community 7"
Cohesion: 0.4
Nodes (1): SharedMemoryPbtTest

### Community 8 - "Community 8"
Cohesion: 0.4
Nodes (1): MessageQueuePbtTest

### Community 9 - "Community 9"
Cohesion: 0.4
Nodes (1): BackgroundWorkerManagerTest

### Community 10 - "Community 10"
Cohesion: 1.0
Nodes (0): 

### Community 11 - "Community 11"
Cohesion: 1.0
Nodes (0): 

## Knowledge Gaps
- **Thin community `Community 10`** (1 nodes): `test.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Community 11`** (1 nodes): `bootstrap.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `WorkerInfo` connect `Community 4` to `Community 9`, `Community 3`?**
  _High betweenness centrality (0.106) - this node is a cross-community bridge._
- **Why does `Semaphore` connect `Community 5` to `Community 0`, `Community 1`?**
  _High betweenness centrality (0.080) - this node is a cross-community bridge._
- **Why does `SharedMemoryTest` connect `Community 0` to `Community 1`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._