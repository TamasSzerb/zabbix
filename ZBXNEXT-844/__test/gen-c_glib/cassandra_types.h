/**
 * Autogenerated by Thrift Compiler (0.7.0-dev)
 *
 * DO NOT EDIT UNLESS YOU ARE SURE THAT YOU KNOW WHAT YOU ARE DOING
 */
#ifndef CASSANDRA_TYPES_H
#define CASSANDRA_TYPES_H

/* base includes */
#include <glib-object.h>
#include <thrift_struct.h>
#include <protocol/thrift_protocol.h>

/* custom thrift includes */

/* begin types */

enum _ConsistencyLevel {
  CONSISTENCY_LEVEL_ONE = 1,
  CONSISTENCY_LEVEL_QUORUM = 2,
  CONSISTENCY_LEVEL_LOCAL_QUORUM = 3,
  CONSISTENCY_LEVEL_EACH_QUORUM = 4,
  CONSISTENCY_LEVEL_ALL = 5,
  CONSISTENCY_LEVEL_ANY = 6,
  CONSISTENCY_LEVEL_TWO = 7,
  CONSISTENCY_LEVEL_THREE = 8
};
typedef enum _ConsistencyLevel ConsistencyLevel;

enum _IndexOperator {
  INDEX_OPERATOR_EQ = 0,
  INDEX_OPERATOR_GTE = 1,
  INDEX_OPERATOR_GT = 2,
  INDEX_OPERATOR_LTE = 3,
  INDEX_OPERATOR_LT = 4
};
typedef enum _IndexOperator IndexOperator;

enum _IndexType {
  INDEX_TYPE_KEYS = 0
};
typedef enum _IndexType IndexType;

enum _Compression {
  COMPRESSION_GZIP = 1,
  COMPRESSION_NONE = 2
};
typedef enum _Compression Compression;

enum _CqlResultType {
  CQL_RESULT_TYPE_ROWS = 1,
  CQL_RESULT_TYPE_VOID = 2,
  CQL_RESULT_TYPE_INT = 3
};
typedef enum _CqlResultType CqlResultType;

/* constants */
#define VERSION "19.10.0"

/* struct Column */
struct _Column
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * name;
  GByteArray * value;
  gboolean __isset_value;
  gint64 timestamp;
  gboolean __isset_timestamp;
  gint32 ttl;
  gboolean __isset_ttl;
};
typedef struct _Column Column;

struct _ColumnClass
{
  ThriftStructClass parent;
};
typedef struct _ColumnClass ColumnClass;

GType column_get_type (void);
#define TYPE_COLUMN (column_get_type())
#define COLUMN(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COLUMN, Column))
#define COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COLUMN, ColumnClass))
#define IS_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COLUMN))
#define IS_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COLUMN))
#define COLUMN_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COLUMN, ColumnClass))

/* struct SuperColumn */
struct _SuperColumn
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * name;
  GPtrArray * columns;
};
typedef struct _SuperColumn SuperColumn;

struct _SuperColumnClass
{
  ThriftStructClass parent;
};
typedef struct _SuperColumnClass SuperColumnClass;

GType super_column_get_type (void);
#define TYPE_SUPER_COLUMN (super_column_get_type())
#define SUPER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_SUPER_COLUMN, SuperColumn))
#define SUPER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_SUPER_COLUMN, SuperColumnClass))
#define IS_SUPER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_SUPER_COLUMN))
#define IS_SUPER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_SUPER_COLUMN))
#define SUPER_COLUMN_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_SUPER_COLUMN, SuperColumnClass))

/* struct CounterColumn */
struct _CounterColumn
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * name;
  gint64 value;
};
typedef struct _CounterColumn CounterColumn;

struct _CounterColumnClass
{
  ThriftStructClass parent;
};
typedef struct _CounterColumnClass CounterColumnClass;

GType counter_column_get_type (void);
#define TYPE_COUNTER_COLUMN (counter_column_get_type())
#define COUNTER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COUNTER_COLUMN, CounterColumn))
#define COUNTER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COUNTER_COLUMN, CounterColumnClass))
#define IS_COUNTER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COUNTER_COLUMN))
#define IS_COUNTER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COUNTER_COLUMN))
#define COUNTER_COLUMN_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COUNTER_COLUMN, CounterColumnClass))

/* struct CounterSuperColumn */
struct _CounterSuperColumn
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * name;
  GPtrArray * columns;
};
typedef struct _CounterSuperColumn CounterSuperColumn;

struct _CounterSuperColumnClass
{
  ThriftStructClass parent;
};
typedef struct _CounterSuperColumnClass CounterSuperColumnClass;

GType counter_super_column_get_type (void);
#define TYPE_COUNTER_SUPER_COLUMN (counter_super_column_get_type())
#define COUNTER_SUPER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COUNTER_SUPER_COLUMN, CounterSuperColumn))
#define COUNTER_SUPER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COUNTER_SUPER_COLUMN, CounterSuperColumnClass))
#define IS_COUNTER_SUPER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COUNTER_SUPER_COLUMN))
#define IS_COUNTER_SUPER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COUNTER_SUPER_COLUMN))
#define COUNTER_SUPER_COLUMN_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COUNTER_SUPER_COLUMN, CounterSuperColumnClass))

/* struct ColumnOrSuperColumn */
struct _ColumnOrSuperColumn
{ 
  ThriftStruct parent; 

  /* public */
  Column * column;
  gboolean __isset_column;
  SuperColumn * super_column;
  gboolean __isset_super_column;
  CounterColumn * counter_column;
  gboolean __isset_counter_column;
  CounterSuperColumn * counter_super_column;
  gboolean __isset_counter_super_column;
};
typedef struct _ColumnOrSuperColumn ColumnOrSuperColumn;

struct _ColumnOrSuperColumnClass
{
  ThriftStructClass parent;
};
typedef struct _ColumnOrSuperColumnClass ColumnOrSuperColumnClass;

GType column_or_super_column_get_type (void);
#define TYPE_COLUMN_OR_SUPER_COLUMN (column_or_super_column_get_type())
#define COLUMN_OR_SUPER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COLUMN_OR_SUPER_COLUMN, ColumnOrSuperColumn))
#define COLUMN_OR_SUPER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COLUMN_OR_SUPER_COLUMN, ColumnOrSuperColumnClass))
#define IS_COLUMN_OR_SUPER_COLUMN(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COLUMN_OR_SUPER_COLUMN))
#define IS_COLUMN_OR_SUPER_COLUMN_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COLUMN_OR_SUPER_COLUMN))
#define COLUMN_OR_SUPER_COLUMN_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COLUMN_OR_SUPER_COLUMN, ColumnOrSuperColumnClass))

struct _NotFoundException
{ 
  ThriftStruct parent; 

  /* public */
};
typedef struct _NotFoundException NotFoundException;

struct _NotFoundExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _NotFoundExceptionClass NotFoundExceptionClass;

GType not_found_exception_get_type (void);
#define TYPE_NOT_FOUND_EXCEPTION (not_found_exception_get_type())
#define NOT_FOUND_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_NOT_FOUND_EXCEPTION, NotFoundException))
#define NOT_FOUND_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_NOT_FOUND_EXCEPTION, NotFoundExceptionClass))
#define IS_NOT_FOUND_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_NOT_FOUND_EXCEPTION))
#define IS_NOT_FOUND_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_NOT_FOUND_EXCEPTION))
#define NOT_FOUND_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_NOT_FOUND_EXCEPTION, NotFoundExceptionClass))

/* exception */
typedef enum
{
  NOT_FOUND_EXCEPTION_ERROR_CODE,
} NotFoundExceptionError;

GQuark not_found_exception_error_quark (void);
#define NOT_FOUND_EXCEPTION_ERROR (not_found_exception_error_quark())


struct _InvalidRequestException
{ 
  ThriftStruct parent; 

  /* public */
  gchar * why;
};
typedef struct _InvalidRequestException InvalidRequestException;

struct _InvalidRequestExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _InvalidRequestExceptionClass InvalidRequestExceptionClass;

GType invalid_request_exception_get_type (void);
#define TYPE_INVALID_REQUEST_EXCEPTION (invalid_request_exception_get_type())
#define INVALID_REQUEST_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_INVALID_REQUEST_EXCEPTION, InvalidRequestException))
#define INVALID_REQUEST_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_INVALID_REQUEST_EXCEPTION, InvalidRequestExceptionClass))
#define IS_INVALID_REQUEST_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_INVALID_REQUEST_EXCEPTION))
#define IS_INVALID_REQUEST_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_INVALID_REQUEST_EXCEPTION))
#define INVALID_REQUEST_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_INVALID_REQUEST_EXCEPTION, InvalidRequestExceptionClass))

/* exception */
typedef enum
{
  INVALID_REQUEST_EXCEPTION_ERROR_CODE,
} InvalidRequestExceptionError;

GQuark invalid_request_exception_error_quark (void);
#define INVALID_REQUEST_EXCEPTION_ERROR (invalid_request_exception_error_quark())


struct _UnavailableException
{ 
  ThriftStruct parent; 

  /* public */
};
typedef struct _UnavailableException UnavailableException;

struct _UnavailableExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _UnavailableExceptionClass UnavailableExceptionClass;

GType unavailable_exception_get_type (void);
#define TYPE_UNAVAILABLE_EXCEPTION (unavailable_exception_get_type())
#define UNAVAILABLE_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_UNAVAILABLE_EXCEPTION, UnavailableException))
#define UNAVAILABLE_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_UNAVAILABLE_EXCEPTION, UnavailableExceptionClass))
#define IS_UNAVAILABLE_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_UNAVAILABLE_EXCEPTION))
#define IS_UNAVAILABLE_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_UNAVAILABLE_EXCEPTION))
#define UNAVAILABLE_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_UNAVAILABLE_EXCEPTION, UnavailableExceptionClass))

/* exception */
typedef enum
{
  UNAVAILABLE_EXCEPTION_ERROR_CODE,
} UnavailableExceptionError;

GQuark unavailable_exception_error_quark (void);
#define UNAVAILABLE_EXCEPTION_ERROR (unavailable_exception_error_quark())


struct _TimedOutException
{ 
  ThriftStruct parent; 

  /* public */
};
typedef struct _TimedOutException TimedOutException;

struct _TimedOutExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _TimedOutExceptionClass TimedOutExceptionClass;

GType timed_out_exception_get_type (void);
#define TYPE_TIMED_OUT_EXCEPTION (timed_out_exception_get_type())
#define TIMED_OUT_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_TIMED_OUT_EXCEPTION, TimedOutException))
#define TIMED_OUT_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_TIMED_OUT_EXCEPTION, TimedOutExceptionClass))
#define IS_TIMED_OUT_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_TIMED_OUT_EXCEPTION))
#define IS_TIMED_OUT_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_TIMED_OUT_EXCEPTION))
#define TIMED_OUT_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_TIMED_OUT_EXCEPTION, TimedOutExceptionClass))

/* exception */
typedef enum
{
  TIMED_OUT_EXCEPTION_ERROR_CODE,
} TimedOutExceptionError;

GQuark timed_out_exception_error_quark (void);
#define TIMED_OUT_EXCEPTION_ERROR (timed_out_exception_error_quark())


struct _AuthenticationException
{ 
  ThriftStruct parent; 

  /* public */
  gchar * why;
};
typedef struct _AuthenticationException AuthenticationException;

struct _AuthenticationExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _AuthenticationExceptionClass AuthenticationExceptionClass;

GType authentication_exception_get_type (void);
#define TYPE_AUTHENTICATION_EXCEPTION (authentication_exception_get_type())
#define AUTHENTICATION_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_AUTHENTICATION_EXCEPTION, AuthenticationException))
#define AUTHENTICATION_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_AUTHENTICATION_EXCEPTION, AuthenticationExceptionClass))
#define IS_AUTHENTICATION_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_AUTHENTICATION_EXCEPTION))
#define IS_AUTHENTICATION_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_AUTHENTICATION_EXCEPTION))
#define AUTHENTICATION_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_AUTHENTICATION_EXCEPTION, AuthenticationExceptionClass))

/* exception */
typedef enum
{
  AUTHENTICATION_EXCEPTION_ERROR_CODE,
} AuthenticationExceptionError;

GQuark authentication_exception_error_quark (void);
#define AUTHENTICATION_EXCEPTION_ERROR (authentication_exception_error_quark())


struct _AuthorizationException
{ 
  ThriftStruct parent; 

  /* public */
  gchar * why;
};
typedef struct _AuthorizationException AuthorizationException;

struct _AuthorizationExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _AuthorizationExceptionClass AuthorizationExceptionClass;

GType authorization_exception_get_type (void);
#define TYPE_AUTHORIZATION_EXCEPTION (authorization_exception_get_type())
#define AUTHORIZATION_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_AUTHORIZATION_EXCEPTION, AuthorizationException))
#define AUTHORIZATION_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_AUTHORIZATION_EXCEPTION, AuthorizationExceptionClass))
#define IS_AUTHORIZATION_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_AUTHORIZATION_EXCEPTION))
#define IS_AUTHORIZATION_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_AUTHORIZATION_EXCEPTION))
#define AUTHORIZATION_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_AUTHORIZATION_EXCEPTION, AuthorizationExceptionClass))

/* exception */
typedef enum
{
  AUTHORIZATION_EXCEPTION_ERROR_CODE,
} AuthorizationExceptionError;

GQuark authorization_exception_error_quark (void);
#define AUTHORIZATION_EXCEPTION_ERROR (authorization_exception_error_quark())


struct _SchemaDisagreementException
{ 
  ThriftStruct parent; 

  /* public */
};
typedef struct _SchemaDisagreementException SchemaDisagreementException;

struct _SchemaDisagreementExceptionClass
{
  ThriftStructClass parent;
};
typedef struct _SchemaDisagreementExceptionClass SchemaDisagreementExceptionClass;

GType schema_disagreement_exception_get_type (void);
#define TYPE_SCHEMA_DISAGREEMENT_EXCEPTION (schema_disagreement_exception_get_type())
#define SCHEMA_DISAGREEMENT_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_SCHEMA_DISAGREEMENT_EXCEPTION, SchemaDisagreementException))
#define SCHEMA_DISAGREEMENT_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_SCHEMA_DISAGREEMENT_EXCEPTION, SchemaDisagreementExceptionClass))
#define IS_SCHEMA_DISAGREEMENT_EXCEPTION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_SCHEMA_DISAGREEMENT_EXCEPTION))
#define IS_SCHEMA_DISAGREEMENT_EXCEPTION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_SCHEMA_DISAGREEMENT_EXCEPTION))
#define SCHEMA_DISAGREEMENT_EXCEPTION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_SCHEMA_DISAGREEMENT_EXCEPTION, SchemaDisagreementExceptionClass))

/* exception */
typedef enum
{
  SCHEMA_DISAGREEMENT_EXCEPTION_ERROR_CODE,
} SchemaDisagreementExceptionError;

GQuark schema_disagreement_exception_error_quark (void);
#define SCHEMA_DISAGREEMENT_EXCEPTION_ERROR (schema_disagreement_exception_error_quark())


/* struct ColumnParent */
struct _ColumnParent
{ 
  ThriftStruct parent; 

  /* public */
  gchar * column_family;
  GByteArray * super_column;
  gboolean __isset_super_column;
};
typedef struct _ColumnParent ColumnParent;

struct _ColumnParentClass
{
  ThriftStructClass parent;
};
typedef struct _ColumnParentClass ColumnParentClass;

GType column_parent_get_type (void);
#define TYPE_COLUMN_PARENT (column_parent_get_type())
#define COLUMN_PARENT(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COLUMN_PARENT, ColumnParent))
#define COLUMN_PARENT_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COLUMN_PARENT, ColumnParentClass))
#define IS_COLUMN_PARENT(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COLUMN_PARENT))
#define IS_COLUMN_PARENT_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COLUMN_PARENT))
#define COLUMN_PARENT_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COLUMN_PARENT, ColumnParentClass))

/* struct ColumnPath */
struct _ColumnPath
{ 
  ThriftStruct parent; 

  /* public */
  gchar * column_family;
  GByteArray * super_column;
  gboolean __isset_super_column;
  GByteArray * column;
  gboolean __isset_column;
};
typedef struct _ColumnPath ColumnPath;

struct _ColumnPathClass
{
  ThriftStructClass parent;
};
typedef struct _ColumnPathClass ColumnPathClass;

GType column_path_get_type (void);
#define TYPE_COLUMN_PATH (column_path_get_type())
#define COLUMN_PATH(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COLUMN_PATH, ColumnPath))
#define COLUMN_PATH_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COLUMN_PATH, ColumnPathClass))
#define IS_COLUMN_PATH(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COLUMN_PATH))
#define IS_COLUMN_PATH_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COLUMN_PATH))
#define COLUMN_PATH_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COLUMN_PATH, ColumnPathClass))

/* struct SliceRange */
struct _SliceRange
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * start;
  GByteArray * finish;
  gboolean reversed;
  gint32 count;
};
typedef struct _SliceRange SliceRange;

struct _SliceRangeClass
{
  ThriftStructClass parent;
};
typedef struct _SliceRangeClass SliceRangeClass;

GType slice_range_get_type (void);
#define TYPE_SLICE_RANGE (slice_range_get_type())
#define SLICE_RANGE(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_SLICE_RANGE, SliceRange))
#define SLICE_RANGE_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_SLICE_RANGE, SliceRangeClass))
#define IS_SLICE_RANGE(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_SLICE_RANGE))
#define IS_SLICE_RANGE_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_SLICE_RANGE))
#define SLICE_RANGE_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_SLICE_RANGE, SliceRangeClass))

/* struct SlicePredicate */
struct _SlicePredicate
{ 
  ThriftStruct parent; 

  /* public */
  GPtrArray * column_names;
  gboolean __isset_column_names;
  SliceRange * slice_range;
  gboolean __isset_slice_range;
};
typedef struct _SlicePredicate SlicePredicate;

struct _SlicePredicateClass
{
  ThriftStructClass parent;
};
typedef struct _SlicePredicateClass SlicePredicateClass;

GType slice_predicate_get_type (void);
#define TYPE_SLICE_PREDICATE (slice_predicate_get_type())
#define SLICE_PREDICATE(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_SLICE_PREDICATE, SlicePredicate))
#define SLICE_PREDICATE_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_SLICE_PREDICATE, SlicePredicateClass))
#define IS_SLICE_PREDICATE(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_SLICE_PREDICATE))
#define IS_SLICE_PREDICATE_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_SLICE_PREDICATE))
#define SLICE_PREDICATE_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_SLICE_PREDICATE, SlicePredicateClass))

/* struct IndexExpression */
struct _IndexExpression
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * column_name;
  IndexOperator op;
  GByteArray * value;
};
typedef struct _IndexExpression IndexExpression;

struct _IndexExpressionClass
{
  ThriftStructClass parent;
};
typedef struct _IndexExpressionClass IndexExpressionClass;

GType index_expression_get_type (void);
#define TYPE_INDEX_EXPRESSION (index_expression_get_type())
#define INDEX_EXPRESSION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_INDEX_EXPRESSION, IndexExpression))
#define INDEX_EXPRESSION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_INDEX_EXPRESSION, IndexExpressionClass))
#define IS_INDEX_EXPRESSION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_INDEX_EXPRESSION))
#define IS_INDEX_EXPRESSION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_INDEX_EXPRESSION))
#define INDEX_EXPRESSION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_INDEX_EXPRESSION, IndexExpressionClass))

/* struct IndexClause */
struct _IndexClause
{ 
  ThriftStruct parent; 

  /* public */
  GPtrArray * expressions;
  GByteArray * start_key;
  gint32 count;
};
typedef struct _IndexClause IndexClause;

struct _IndexClauseClass
{
  ThriftStructClass parent;
};
typedef struct _IndexClauseClass IndexClauseClass;

GType index_clause_get_type (void);
#define TYPE_INDEX_CLAUSE (index_clause_get_type())
#define INDEX_CLAUSE(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_INDEX_CLAUSE, IndexClause))
#define INDEX_CLAUSE_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_INDEX_CLAUSE, IndexClauseClass))
#define IS_INDEX_CLAUSE(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_INDEX_CLAUSE))
#define IS_INDEX_CLAUSE_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_INDEX_CLAUSE))
#define INDEX_CLAUSE_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_INDEX_CLAUSE, IndexClauseClass))

/* struct KeyRange */
struct _KeyRange
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * start_key;
  gboolean __isset_start_key;
  GByteArray * end_key;
  gboolean __isset_end_key;
  gchar * start_token;
  gboolean __isset_start_token;
  gchar * end_token;
  gboolean __isset_end_token;
  gint32 count;
};
typedef struct _KeyRange KeyRange;

struct _KeyRangeClass
{
  ThriftStructClass parent;
};
typedef struct _KeyRangeClass KeyRangeClass;

GType key_range_get_type (void);
#define TYPE_KEY_RANGE (key_range_get_type())
#define KEY_RANGE(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_KEY_RANGE, KeyRange))
#define KEY_RANGE_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_KEY_RANGE, KeyRangeClass))
#define IS_KEY_RANGE(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_KEY_RANGE))
#define IS_KEY_RANGE_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_KEY_RANGE))
#define KEY_RANGE_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_KEY_RANGE, KeyRangeClass))

/* struct KeySlice */
struct _KeySlice
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * key;
  GPtrArray * columns;
};
typedef struct _KeySlice KeySlice;

struct _KeySliceClass
{
  ThriftStructClass parent;
};
typedef struct _KeySliceClass KeySliceClass;

GType key_slice_get_type (void);
#define TYPE_KEY_SLICE (key_slice_get_type())
#define KEY_SLICE(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_KEY_SLICE, KeySlice))
#define KEY_SLICE_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_KEY_SLICE, KeySliceClass))
#define IS_KEY_SLICE(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_KEY_SLICE))
#define IS_KEY_SLICE_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_KEY_SLICE))
#define KEY_SLICE_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_KEY_SLICE, KeySliceClass))

/* struct KeyCount */
struct _KeyCount
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * key;
  gint32 count;
};
typedef struct _KeyCount KeyCount;

struct _KeyCountClass
{
  ThriftStructClass parent;
};
typedef struct _KeyCountClass KeyCountClass;

GType key_count_get_type (void);
#define TYPE_KEY_COUNT (key_count_get_type())
#define KEY_COUNT(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_KEY_COUNT, KeyCount))
#define KEY_COUNT_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_KEY_COUNT, KeyCountClass))
#define IS_KEY_COUNT(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_KEY_COUNT))
#define IS_KEY_COUNT_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_KEY_COUNT))
#define KEY_COUNT_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_KEY_COUNT, KeyCountClass))

/* struct Deletion */
struct _Deletion
{ 
  ThriftStruct parent; 

  /* public */
  gint64 timestamp;
  gboolean __isset_timestamp;
  GByteArray * super_column;
  gboolean __isset_super_column;
  SlicePredicate * predicate;
  gboolean __isset_predicate;
};
typedef struct _Deletion Deletion;

struct _DeletionClass
{
  ThriftStructClass parent;
};
typedef struct _DeletionClass DeletionClass;

GType deletion_get_type (void);
#define TYPE_DELETION (deletion_get_type())
#define DELETION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_DELETION, Deletion))
#define DELETION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_DELETION, DeletionClass))
#define IS_DELETION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_DELETION))
#define IS_DELETION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_DELETION))
#define DELETION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_DELETION, DeletionClass))

/* struct Mutation */
struct _Mutation
{ 
  ThriftStruct parent; 

  /* public */
  ColumnOrSuperColumn * column_or_supercolumn;
  gboolean __isset_column_or_supercolumn;
  Deletion * deletion;
  gboolean __isset_deletion;
};
typedef struct _Mutation Mutation;

struct _MutationClass
{
  ThriftStructClass parent;
};
typedef struct _MutationClass MutationClass;

GType mutation_get_type (void);
#define TYPE_MUTATION (mutation_get_type())
#define MUTATION(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_MUTATION, Mutation))
#define MUTATION_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_MUTATION, MutationClass))
#define IS_MUTATION(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_MUTATION))
#define IS_MUTATION_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_MUTATION))
#define MUTATION_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_MUTATION, MutationClass))

/* struct TokenRange */
struct _TokenRange
{ 
  ThriftStruct parent; 

  /* public */
  gchar * start_token;
  gchar * end_token;
  GPtrArray * endpoints;
};
typedef struct _TokenRange TokenRange;

struct _TokenRangeClass
{
  ThriftStructClass parent;
};
typedef struct _TokenRangeClass TokenRangeClass;

GType token_range_get_type (void);
#define TYPE_TOKEN_RANGE (token_range_get_type())
#define TOKEN_RANGE(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_TOKEN_RANGE, TokenRange))
#define TOKEN_RANGE_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_TOKEN_RANGE, TokenRangeClass))
#define IS_TOKEN_RANGE(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_TOKEN_RANGE))
#define IS_TOKEN_RANGE_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_TOKEN_RANGE))
#define TOKEN_RANGE_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_TOKEN_RANGE, TokenRangeClass))

/* struct AuthenticationRequest */
struct _AuthenticationRequest
{ 
  ThriftStruct parent; 

  /* public */
  GHashTable * credentials;
};
typedef struct _AuthenticationRequest AuthenticationRequest;

struct _AuthenticationRequestClass
{
  ThriftStructClass parent;
};
typedef struct _AuthenticationRequestClass AuthenticationRequestClass;

GType authentication_request_get_type (void);
#define TYPE_AUTHENTICATION_REQUEST (authentication_request_get_type())
#define AUTHENTICATION_REQUEST(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_AUTHENTICATION_REQUEST, AuthenticationRequest))
#define AUTHENTICATION_REQUEST_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_AUTHENTICATION_REQUEST, AuthenticationRequestClass))
#define IS_AUTHENTICATION_REQUEST(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_AUTHENTICATION_REQUEST))
#define IS_AUTHENTICATION_REQUEST_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_AUTHENTICATION_REQUEST))
#define AUTHENTICATION_REQUEST_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_AUTHENTICATION_REQUEST, AuthenticationRequestClass))

/* struct ColumnDef */
struct _ColumnDef
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * name;
  gchar * validation_class;
  IndexType index_type;
  gboolean __isset_index_type;
  gchar * index_name;
  gboolean __isset_index_name;
};
typedef struct _ColumnDef ColumnDef;

struct _ColumnDefClass
{
  ThriftStructClass parent;
};
typedef struct _ColumnDefClass ColumnDefClass;

GType column_def_get_type (void);
#define TYPE_COLUMN_DEF (column_def_get_type())
#define COLUMN_DEF(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_COLUMN_DEF, ColumnDef))
#define COLUMN_DEF_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_COLUMN_DEF, ColumnDefClass))
#define IS_COLUMN_DEF(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_COLUMN_DEF))
#define IS_COLUMN_DEF_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_COLUMN_DEF))
#define COLUMN_DEF_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_COLUMN_DEF, ColumnDefClass))

/* struct CfDef */
struct _CfDef
{ 
  ThriftStruct parent; 

  /* public */
  gchar * keyspace;
  gchar * name;
  gchar * column_type;
  gboolean __isset_column_type;
  gchar * comparator_type;
  gboolean __isset_comparator_type;
  gchar * subcomparator_type;
  gboolean __isset_subcomparator_type;
  gchar * comment;
  gboolean __isset_comment;
  gdouble row_cache_size;
  gboolean __isset_row_cache_size;
  gdouble key_cache_size;
  gboolean __isset_key_cache_size;
  gdouble read_repair_chance;
  gboolean __isset_read_repair_chance;
  GPtrArray * column_metadata;
  gboolean __isset_column_metadata;
  gint32 gc_grace_seconds;
  gboolean __isset_gc_grace_seconds;
  gchar * default_validation_class;
  gboolean __isset_default_validation_class;
  gint32 id;
  gboolean __isset_id;
  gint32 min_compaction_threshold;
  gboolean __isset_min_compaction_threshold;
  gint32 max_compaction_threshold;
  gboolean __isset_max_compaction_threshold;
  gint32 row_cache_save_period_in_seconds;
  gboolean __isset_row_cache_save_period_in_seconds;
  gint32 key_cache_save_period_in_seconds;
  gboolean __isset_key_cache_save_period_in_seconds;
  gint32 memtable_flush_after_mins;
  gboolean __isset_memtable_flush_after_mins;
  gint32 memtable_throughput_in_mb;
  gboolean __isset_memtable_throughput_in_mb;
  gdouble memtable_operations_in_millions;
  gboolean __isset_memtable_operations_in_millions;
  gboolean replicate_on_write;
  gboolean __isset_replicate_on_write;
  gdouble merge_shards_chance;
  gboolean __isset_merge_shards_chance;
  gchar * key_validation_class;
  gboolean __isset_key_validation_class;
  gchar * row_cache_provider;
  gboolean __isset_row_cache_provider;
  GByteArray * key_alias;
  gboolean __isset_key_alias;
};
typedef struct _CfDef CfDef;

struct _CfDefClass
{
  ThriftStructClass parent;
};
typedef struct _CfDefClass CfDefClass;

GType cf_def_get_type (void);
#define TYPE_CF_DEF (cf_def_get_type())
#define CF_DEF(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_CF_DEF, CfDef))
#define CF_DEF_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_CF_DEF, CfDefClass))
#define IS_CF_DEF(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_CF_DEF))
#define IS_CF_DEF_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_CF_DEF))
#define CF_DEF_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_CF_DEF, CfDefClass))

/* struct KsDef */
struct _KsDef
{ 
  ThriftStruct parent; 

  /* public */
  gchar * name;
  gchar * strategy_class;
  GHashTable * strategy_options;
  gboolean __isset_strategy_options;
  gint32 replication_factor;
  gboolean __isset_replication_factor;
  GPtrArray * cf_defs;
  gboolean durable_writes;
  gboolean __isset_durable_writes;
};
typedef struct _KsDef KsDef;

struct _KsDefClass
{
  ThriftStructClass parent;
};
typedef struct _KsDefClass KsDefClass;

GType ks_def_get_type (void);
#define TYPE_KS_DEF (ks_def_get_type())
#define KS_DEF(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_KS_DEF, KsDef))
#define KS_DEF_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_KS_DEF, KsDefClass))
#define IS_KS_DEF(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_KS_DEF))
#define IS_KS_DEF_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_KS_DEF))
#define KS_DEF_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_KS_DEF, KsDefClass))

/* struct CqlRow */
struct _CqlRow
{ 
  ThriftStruct parent; 

  /* public */
  GByteArray * key;
  GPtrArray * columns;
};
typedef struct _CqlRow CqlRow;

struct _CqlRowClass
{
  ThriftStructClass parent;
};
typedef struct _CqlRowClass CqlRowClass;

GType cql_row_get_type (void);
#define TYPE_CQL_ROW (cql_row_get_type())
#define CQL_ROW(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_CQL_ROW, CqlRow))
#define CQL_ROW_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_CQL_ROW, CqlRowClass))
#define IS_CQL_ROW(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_CQL_ROW))
#define IS_CQL_ROW_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_CQL_ROW))
#define CQL_ROW_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_CQL_ROW, CqlRowClass))

/* struct CqlResult */
struct _CqlResult
{ 
  ThriftStruct parent; 

  /* public */
  CqlResultType type;
  GPtrArray * rows;
  gboolean __isset_rows;
  gint32 num;
  gboolean __isset_num;
};
typedef struct _CqlResult CqlResult;

struct _CqlResultClass
{
  ThriftStructClass parent;
};
typedef struct _CqlResultClass CqlResultClass;

GType cql_result_get_type (void);
#define TYPE_CQL_RESULT (cql_result_get_type())
#define CQL_RESULT(obj) (G_TYPE_CHECK_INSTANCE_CAST ((obj), TYPE_CQL_RESULT, CqlResult))
#define CQL_RESULT_CLASS(c) (G_TYPE_CHECK_CLASS_CAST ((c), _TYPE_CQL_RESULT, CqlResultClass))
#define IS_CQL_RESULT(obj) (G_TYPE_CHECK_INSTANCE_TYPE ((obj), TYPE_CQL_RESULT))
#define IS_CQL_RESULT_CLASS(c) (G_TYPE_CHECK_CLASS_TYPE ((c), TYPE_CQL_RESULT))
#define CQL_RESULT_GET_CLASS(obj) (G_TYPE_INSTANCE_GET_CLASS ((obj), TYPE_CQL_RESULT, CqlResultClass))

#endif /* CASSANDRA_TYPES_H */
